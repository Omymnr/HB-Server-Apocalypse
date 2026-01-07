using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Net.Http;
using System.Net.Sockets;
using System.Runtime.InteropServices;
using System.Security.Cryptography;
using System.Threading;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Input;
using System.Windows.Interop;
using System.Windows.Media;
using System.Windows.Media.Effects;
using System.Windows.Threading;
using Newtonsoft.Json;
using MessageBox = System.Windows.MessageBox;

namespace ApocalypseLauncher
{
    public class GameFile
    {
        public string Name { get; set; }
        public string Hash { get; set; }
        public long Size { get; set; }
    }

    public class LauncherConfig
    {
        public double Width { get; set; } = 900;
        public double Height { get; set; } = 630;
        public bool IsSpanish { get; set; } = false;
    }

    public partial class MainWindow : Window
    {
        // ================= CONFIGURACIÓN =================
        private const string UPDATE_BASE_URL = "https://hb-apocalypse.com/updates/";
        private const string LIST_FILE_NAME = "files.txt";

        private static readonly TimeSpan HOT_UPDATE_INTERVAL = TimeSpan.FromMinutes(5);
        private static readonly TimeSpan SERVER_STATUS_REFRESH_INTERVAL = TimeSpan.FromSeconds(10);

        // --- CONFIGURACIÓN DE CONEXIÓN ---
        private const string SERVER_IP = "212.47.64.121";
        private const int SERVER_STATUS_PORT = 8531;
        private const string GAME_EXE = "HelbreathApocalypse.exe";

        private const string NEWS_URL_ENG = "https://hb-apocalypse.com/ApocalypseNews.txt";
        private const string NEWS_URL_ESP = "https://hb-apocalypse.com/ApocalypseNoticias.txt";

        // La instancia única se gestiona en App.xaml.cs
        // =================================================

        [DllImport("user32.dll")]
        private static extern bool SetForegroundWindow(IntPtr hWnd);

        [DllImport("user32.dll")]
        private static extern bool ShowWindow(IntPtr hWnd, int nCmdShow);
        private const int SW_RESTORE = 9;

        private HttpClient _httpClient = new HttpClient();
        private string _rootPath = AppDomain.CurrentDomain.BaseDirectory;
        private string _configPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "launcher_config.json");
        private string _gameSettingsPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "DATA", "Settings.cfg");
        private string _gameConfigPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "GameConfig.ini");
        private bool _isSpanish = false;
        private int _selectedResolution = 0; // 0=1024x768, 1=800x600, 2=640x480
        private bool _selectedFullscreen = true; // Default: fullscreen enabled

        private DispatcherTimer? _hotUpdateTimer;
        private bool _updateInProgress;

        private DispatcherTimer? _serverStatusTimer;
        private int _serverStatusCheckInProgress;

        private bool _hasPendingFileUpdates;

        public MainWindow()
        {
            InitializeComponent();
            CleanupLegacyUpdateArtifacts();
            LoadCustomConfig();
            LoadGameSettings();

            // Restore placement as early as possible (before Loaded) to avoid a visible "jump".
            SourceInitialized += (_, __) => RestoreWindowPlacementFromSettings();
        }

        private async void Window_Loaded(object sender, RoutedEventArgs e)
        {
            UpdateLanguageTexts();
            await CheckServerStatus();
            StartServerStatusAutoRefresh();
            await LoadNewsText();
            await CheckForUpdates(showUiDuringCheck: true);
            StartHotUpdateChecks();
        }

        private void StartServerStatusAutoRefresh()
        {
            if (_serverStatusTimer != null)
            {
                return;
            }

            _serverStatusTimer = new DispatcherTimer(DispatcherPriority.Background)
            {
                Interval = SERVER_STATUS_REFRESH_INTERVAL
            };

            _serverStatusTimer.Tick += async (_, __) =>
            {
                if (!IsLoaded)
                {
                    return;
                }

                // Avoid overlapping checks if a previous TCP attempt is still running.
                if (Interlocked.Exchange(ref _serverStatusCheckInProgress, 1) == 1)
                {
                    return;
                }

                try
                {
                    await CheckServerStatus();
                }
                finally
                {
                    Interlocked.Exchange(ref _serverStatusCheckInProgress, 0);
                }
            };

            _serverStatusTimer.Start();
        }

        private void RestoreWindowPlacementFromSettings()
        {
            try
            {
                var settings = Properties.Settings.Default;
                if (!settings.HasWindowPlacement)
                {
                    return;
                }

                // Size (from last normal/maximized restore bounds)
                if (IsFinitePositive(settings.WindowWidth) && IsFinitePositive(settings.WindowHeight))
                {
                    Width = settings.WindowWidth;
                    Height = settings.WindowHeight;
                }

                // Position
                if (IsFinite(settings.WindowLeft) && IsFinite(settings.WindowTop))
                {
                    var desiredRect = new Rect(settings.WindowLeft, settings.WindowTop, Width, Height);
                    if (IsWindowRectVisible(desiredRect))
                    {
                        Left = settings.WindowLeft;
                        Top = settings.WindowTop;
                    }
                }

                // State (set last)
                var state = settings.WindowState;
                if (state == (int)System.Windows.WindowState.Maximized)
                {
                    WindowState = System.Windows.WindowState.Maximized;
                }
            }
            catch
            {
                // best-effort
            }
        }

        private void SaveWindowPlacementToSettings()
        {
            try
            {
                var settings = Properties.Settings.Default;

                // If minimized, restore to normal on next launch.
                var stateToPersist = WindowState == System.Windows.WindowState.Minimized
                    ? System.Windows.WindowState.Normal
                    : WindowState;

                Rect bounds = stateToPersist == System.Windows.WindowState.Normal
                    ? new Rect(Left, Top, Width, Height)
                    : RestoreBounds;

                if (IsFinite(bounds.Left) && IsFinite(bounds.Top) && IsFinitePositive(bounds.Width) && IsFinitePositive(bounds.Height))
                {
                    settings.WindowLeft = bounds.Left;
                    settings.WindowTop = bounds.Top;
                    settings.WindowWidth = bounds.Width;
                    settings.WindowHeight = bounds.Height;
                }

                settings.WindowState = (int)stateToPersist;
                settings.HasWindowPlacement = true;
                settings.Save();
            }
            catch
            {
                // best-effort
            }
        }

        private static bool IsFinite(double value) => !double.IsNaN(value) && !double.IsInfinity(value);
        private static bool IsFinitePositive(double value) => IsFinite(value) && value > 0;

        private static bool IsWindowRectVisible(Rect rect)
        {
            // Ensure the window will be at least partially visible on the current virtual screen.
            var virtualRect = new Rect(
                SystemParameters.VirtualScreenLeft,
                SystemParameters.VirtualScreenTop,
                SystemParameters.VirtualScreenWidth,
                SystemParameters.VirtualScreenHeight);

            var intersection = Rect.Intersect(rect, virtualRect);
            return !intersection.IsEmpty && intersection.Width >= 50 && intersection.Height >= 50;
        }

        // --- ESTADO DEL SERVIDOR ---
        private async Task CheckServerStatus()
        {
            TxtServerStatus.Text = _isSpanish ? "COMPROBANDO..." : "CHECKING...";
            TxtServerStatus.Foreground = Brushes.Orange;

            bool isOnline = false;

            await Task.Run(() =>
            {
                try
                {
                    using (var client = new TcpClient())
                    {
                        var result = client.BeginConnect(SERVER_IP, SERVER_STATUS_PORT, null, null);
                        var success = result.AsyncWaitHandle.WaitOne(TimeSpan.FromSeconds(2));
                        if (success)
                        {
                            client.EndConnect(result);
                            isOnline = true;
                        }
                    }
                }
                catch
                {
                    isOnline = false;
                }
            });

            if (isOnline)
            {
                TxtServerStatus.Text = "ONLINE";
                TxtServerStatus.Foreground = new SolidColorBrush((Color)ColorConverter.ConvertFromString("#00ff00"));
                TxtServerStatus.Effect = new DropShadowEffect
                {
                    Color = Colors.Lime,
                    BlurRadius = 10,
                    ShadowDepth = 0,
                    Opacity = 1
                };
            }
            else
            {
                TxtServerStatus.Text = "OFFLINE";
                TxtServerStatus.Foreground = Brushes.Red;
                TxtServerStatus.Effect = null;
            }
        }

        // --- NOTICIAS ---
        private async Task LoadNewsText()
        {
            try
            {
                string baseUrl = _isSpanish ? NEWS_URL_ESP : NEWS_URL_ENG;
                string url = $"{baseUrl}?t={DateTime.Now.Ticks}";
                string newsContent = await _httpClient.GetStringAsync(url);
                TxtNewsContent.Text = newsContent;
            }
            catch
            {
                TxtNewsContent.Text = _isSpanish ? "No se pudieron cargar las noticias." : "Could not load news.";
            }
        }

        private void NewsScrollViewer_PreviewMouseWheel(object sender, MouseWheelEventArgs e)
        {
            if (sender is not ScrollViewer scrollViewer)
            {
                return;
            }

            // WPF: e.Delta > 0 = wheel up, e.Delta < 0 = wheel down.
            // Natural behavior: wheel down -> content goes down (offset increases).
            double newOffset = scrollViewer.VerticalOffset - e.Delta;
            newOffset = Math.Max(0, Math.Min(scrollViewer.ScrollableHeight, newOffset));
            scrollViewer.ScrollToVerticalOffset(newOffset);
            e.Handled = true;
        }

        // --- ACTUALIZACIÓN ---
        private async Task CheckForUpdates(bool showUiDuringCheck)
        {
            if (_updateInProgress)
            {
                return;
            }

            _updateInProgress = true;

            if (showUiDuringCheck)
            {
                UpdateStatus("Checking files...", "Comprobando archivos...");
                ToggleProgressBar(true);
                BtnPlay.IsEnabled = false;
            }

            try
            {
                if (!IsRootPathWritable())
                {
                    if (showUiDuringCheck)
                    {
                        UpdateStatus(
                            "Cannot update: no write permission in the game folder. Move the game/launcher out of Program Files or run as administrator.",
                            "No se puede actualizar: no hay permisos de escritura en la carpeta del juego. Mueve el juego/launcher fuera de Program Files o ejecútalo como administrador."
                        );
                        ToggleProgressBar(false);
                        if (File.Exists(Path.Combine(_rootPath, GAME_EXE))) EnablePlayButton();
                    }

                    return;
                }

                string listUrl = $"{UPDATE_BASE_URL}{LIST_FILE_NAME}?t={DateTime.Now.Ticks}";
                string listData = await _httpClient.GetStringAsync(listUrl);

                var lines = listData.Split(new[] { '\n', '\r' }, StringSplitOptions.RemoveEmptyEntries);
                List<GameFile> filesToUpdate = new List<GameFile>();

                foreach (string line in lines)
                {
                    var parts = line.Split(new[] { ' ' }, StringSplitOptions.RemoveEmptyEntries);
                    if (parts.Length < 2) continue;

                    var fileObj = new GameFile
                    {
                        Name = parts[0],
                        Hash = parts[1]
                    };

                    string localPath = Path.Combine(_rootPath, fileObj.Name);

                    if (!File.Exists(localPath) || CalculateMD5(localPath) != fileObj.Hash)
                    {
                        filesToUpdate.Add(fileObj);
                    }
                }

                if (filesToUpdate.Count > 0)
                {
                    if (!showUiDuringCheck)
                    {
                        UpdateStatus("Updates found. Downloading...", "Actualización encontrada. Descargando...");
                        ToggleProgressBar(true);
                        BtnPlay.IsEnabled = false;
                    }
                    await DownloadFiles(filesToUpdate);
                }
                else
                {
                    if (showUiDuringCheck)
                    {
                        UpdateStatus("Client is up to date.", "Cliente actualizado.");
                        ToggleProgressBar(false);
                        EnablePlayButton();
                    }
                }
            }
            catch (Exception ex)
            {
                if (showUiDuringCheck)
                {
                    UpdateStatus($"Error: {ex.Message}", $"Error: {ex.Message}");
                    ToggleProgressBar(false);
                    if (File.Exists(Path.Combine(_rootPath, GAME_EXE))) EnablePlayButton();
                }
            }
            finally
            {
                _updateInProgress = false;
            }
        }

        private void StartHotUpdateChecks()
        {
            if (_hotUpdateTimer != null)
            {
                return;
            }

            _hotUpdateTimer = new DispatcherTimer(DispatcherPriority.Background)
            {
                Interval = HOT_UPDATE_INTERVAL
            };

            _hotUpdateTimer.Tick += async (_, __) =>
            {
                if (!IsLoaded)
                {
                    return;
                }

                // Background check: no UI changes unless updates are found.
                await CheckForUpdates(showUiDuringCheck: false);
            };

            _hotUpdateTimer.Start();
        }

        private static Uri BuildUpdateUri(string baseUrl, string relativePath)
        {
            // Ensure each segment is URL-encoded, but keep path separators.
            string normalized = (relativePath ?? string.Empty)
                .Replace('\\', '/')
                .TrimStart('/');

            string[] segments = normalized.Split(new[] { '/' }, StringSplitOptions.RemoveEmptyEntries);
            for (int i = 0; i < segments.Length; i++)
            {
                segments[i] = Uri.EscapeDataString(segments[i]);
            }

            string encodedPath = string.Join("/", segments);
            return new Uri(new Uri(baseUrl, UriKind.Absolute), encodedPath);
        }

        private void LogDownloadFailure(string relativeName, Uri url, string localPath, Exception ex)
        {
            try
            {
                string logPath = Path.Combine(_rootPath, "failed_downloads.txt");

                string statusCode = "";
                if (ex is HttpRequestException hre && hre.StatusCode.HasValue)
                {
                    statusCode = $" status={(int)hre.StatusCode.Value}";
                }

                string line =
                    $"[{DateTime.UtcNow:O}] {relativeName} -> {localPath} url={url}{statusCode} error={ex.GetType().Name}: {ex.Message}{Environment.NewLine}";

                File.AppendAllText(logPath, line);
            }
            catch
            {
                // best-effort logging
            }
        }

        private static bool IsWebConfigPath(string relativePath)
        {
            if (string.IsNullOrWhiteSpace(relativePath))
            {
                return false;
            }

            string fileName = Path.GetFileName(relativePath.Replace('/', '\\'));
            return fileName.Equals("web.config", StringComparison.OrdinalIgnoreCase);
        }

        private async Task DownloadFiles(List<GameFile> files)
        {
            ToggleProgressBar(true);
            double totalFiles = files.Count;
            double current = 0;

            int failedCount = 0;

            string selfExePath = GetSelfExecutablePath();
            string selfFileName = Path.GetFileName(selfExePath);

            foreach (var file in files)
            {
                current++;

                // IIS blocks downloading web.config by design; never treat it as an update payload.
                if (IsWebConfigPath(file.Name))
                {
                    continue;
                }

                UpdateStatus($"Downloading {file.Name}...", $"Descargando {file.Name}...");

                double percentage = (current / totalFiles) * 100;
                PbDownload.Value = percentage;
                TxtPercentage.Text = $"{percentage:F0}%";

                string localPath = Path.Combine(_rootPath, file.Name);
                try
                {
                    Uri fileUri = BuildUpdateUri(UPDATE_BASE_URL, file.Name);
                    byte[] data = await _httpClient.GetByteArrayAsync(fileUri);

                    string? directory = Path.GetDirectoryName(localPath);
                    if (!string.IsNullOrWhiteSpace(directory))
                    {
                        Directory.CreateDirectory(directory);
                    }

                    // If the update includes launcher files (dll/exe/etc), we can't overwrite them while running.
                    // Write as .pending and apply after the launcher exits.
                    if (IsLauncherFileForSelfUpdate(file.Name, selfFileName))
                    {
                        string pendingPath = localPath + ".pending";
                        File.WriteAllBytes(pendingPath, data);
                        _hasPendingFileUpdates = true;
                        continue;
                    }

                    try
                    {
                        File.WriteAllBytes(localPath, data);
                    }
                    catch (IOException)
                    {
                        // Common case: file in use by the launcher or game.
                        string pendingPath = localPath + ".pending";
                        File.WriteAllBytes(pendingPath, data);
                        _hasPendingFileUpdates = true;
                    }
                }
                catch (Exception ex)
                {
                    failedCount++;

                    Uri url = BuildUpdateUri(UPDATE_BASE_URL, file.Name);
                    LogDownloadFailure(file.Name, url, localPath, ex);

                    if (ex is UnauthorizedAccessException)
                    {
                        MessageBox.Show($"Access denied writing {localPath}: {ex.Message}\n\nTry running the launcher as administrator or place the launcher in the game folder where you have write permissions.", "Permission error", MessageBoxButton.OK, MessageBoxImage.Error);
                    }
                }
            }

            if (failedCount > 0)
            {
                UpdateStatus(
                    $"Some files failed to download ({failedCount}). See failed_downloads.txt",
                    $"Fallaron algunas descargas ({failedCount}). Revisa failed_downloads.txt"
                );
            }

            if (_hasPendingFileUpdates)
            {
                UpdateStatus(
                    "Update downloaded. Close and re-open the launcher to apply pending files.",
                    "Actualización descargada. Cierra y vuelve a abrir el launcher para aplicar archivos pendientes."
                );
            }
            else if (failedCount == 0)
            {
                UpdateStatus("Update complete.", "Actualización completada.");
            }
            ToggleProgressBar(false);
            EnablePlayButton();
        }

        private static bool IsLauncherFileForSelfUpdate(string relativeFileName, string selfFileName)
        {
            // Conservative: treat the launcher executable and common .NET app artifacts as self-update files.
            // These are typically locked while running and must be replaced after exit.
            string name = Path.GetFileName(relativeFileName);
            if (string.IsNullOrWhiteSpace(name))
            {
                return false;
            }

            if (name.Equals(selfFileName, StringComparison.OrdinalIgnoreCase))
            {
                return true;
            }

            // Framework-dependent publish often has: ApocalypseLauncher.dll + deps/runtimeconfig.
            if (name.StartsWith("ApocalypseLauncher", StringComparison.OrdinalIgnoreCase))
            {
                return true;
            }

            return false;
        }

        private void ScheduleApplyPendingUpdatesOnExitIfNeeded()
        {
            try
            {
                if (!_hasPendingFileUpdates)
                {
                    return;
                }

                // Create a small cmd in LocalAppData (always writable) that:
                // 1) waits for this PID to exit
                // 2) renames all *.pending under the launcher folder
                // 3) deletes itself
                string logDir = Path.Combine(
                    Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
                    "ApocalypseLauncher");
                Directory.CreateDirectory(logDir);

                string scriptPath = Path.Combine(logDir, "apply_pending_updates.cmd");

                int pid = Process.GetCurrentProcess().Id;
                string root = _rootPath.TrimEnd('\\');

                string script =
                    "@echo off\r\n" +
                    "setlocal EnableExtensions EnableDelayedExpansion\r\n" +
                    $"set PID={pid}\r\n" +
                    $"set ROOT=\"{root}\"\r\n" +
                    ":wait\r\n" +
                    "tasklist /FI \"PID eq %PID%\" 2>nul | find /I \"%PID%\" >nul\r\n" +
                    "if %ERRORLEVEL%==0 (\r\n" +
                    "  timeout /t 1 /nobreak >nul\r\n" +
                    "  goto wait\r\n" +
                    ")\r\n" +
                    "pushd %ROOT%\r\n" +
                    "for /r %%F in (*.pending) do (\r\n" +
                    "  move /Y \"%%F\" \"%%~dpnF\" >nul\r\n" +
                    ")\r\n" +
                    "popd\r\n" +
                    "del /f /q \"%~f0\" >nul 2>&1\r\n";

                File.WriteAllText(scriptPath, script);

                Process.Start(new ProcessStartInfo
                {
                    FileName = "cmd.exe",
                    Arguments = $"/c \"\"{scriptPath}\"\"",
                    UseShellExecute = false,
                    CreateNoWindow = true,
                    WindowStyle = ProcessWindowStyle.Hidden,
                    WorkingDirectory = logDir
                });
            }
            catch
            {
                // best-effort
            }
        }

        private void ToggleProgressBar(bool show)
        {
            if (show)
            {
                PbDownload.Visibility = Visibility.Visible;
                TxtPercentage.Visibility = Visibility.Visible;
            }
            else
            {
                PbDownload.Visibility = Visibility.Collapsed;
                TxtPercentage.Visibility = Visibility.Collapsed;
            }
        }

        private void EnablePlayButton()
        {
            BtnPlay.IsEnabled = true;
        }

        private void UpdateStatus(string eng, string esp)
        {
            TxtStatus.Text = _isSpanish ? esp : eng;
        }

        private static string GetSelfExecutablePath()
        {
            string? processPath = Environment.ProcessPath;
            if (!string.IsNullOrWhiteSpace(processPath))
            {
                return processPath;
            }

            string? mainModulePath = Process.GetCurrentProcess().MainModule?.FileName;
            if (!string.IsNullOrWhiteSpace(mainModulePath))
            {
                return mainModulePath;
            }

            return Path.Combine(AppDomain.CurrentDomain.BaseDirectory, AppDomain.CurrentDomain.FriendlyName);
        }

        private void CleanupLegacyUpdateArtifacts()
        {
            try
            {
                string legacyBat = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "update_launcher.bat");
                if (File.Exists(legacyBat))
                {
                    File.Delete(legacyBat);
                }
            }
            catch
            {
                // best-effort cleanup
            }
        }

        private bool IsRootPathWritable()
        {
            try
            {
                string testFile = Path.Combine(_rootPath, $".__write_test_{Guid.NewGuid():N}.tmp");
                File.WriteAllText(testFile, "test");
                File.Delete(testFile);
                return true;
            }
            catch
            {
                return false;
            }
        }


        private async void BtnPlay_Click(object sender, RoutedEventArgs e)
        {
            string gamePath = Path.Combine(_rootPath, GAME_EXE);
            if (!File.Exists(gamePath))
            {
                MessageBox.Show(_isSpanish ?
                    $"Archivo no encontrado: {GAME_EXE}.\nAsegúrate de poner este Launcher en la carpeta del juego." :
                    $"File not found: {GAME_EXE}.\nMake sure to put this Launcher in the game folder.",
                    "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                return;
            }

            try
            {
                // Save game settings before launching
                SaveGameSettings();

                ProcessStartInfo psi = new ProcessStartInfo();
                psi.FileName = gamePath;
                psi.WorkingDirectory = _rootPath;
                psi.UseShellExecute = true;

                Process.Start(psi);
                await Task.Delay(1000);
                Application.Current.Shutdown();
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error al iniciar el juego: " + ex.Message);
            }
        }

        // --- IDIOMAS ---
        private async void BtnEng_Click(object sender, RoutedEventArgs e)
        {
            _isSpanish = false;
            UpdateLanguageTexts();
            await LoadNewsText();
        }

        private async void BtnEsp_Click(object sender, RoutedEventArgs e)
        {
            _isSpanish = true;
            UpdateLanguageTexts();
            await LoadNewsText();
        }

        private void UpdateLanguageTexts()
        {
            if (_isSpanish)
            {
                BtnEsp.Foreground = Brushes.White;
                BtnEng.Foreground = Brushes.Gray;
                if (TxtSubtitle != null) TxtSubtitle.Text = "";
                if (TxtResolutionLabel != null) TxtResolutionLabel.Text = "RESOLUCIÓN:";
                if (TxtFullscreenLabel != null) TxtFullscreenLabel.Text = "PANTALLA COMPLETA";
                BtnPlay.Content = "JUGAR";

                // LÓGICA CORREGIDA PARA EL STATUS
                if (BtnPlay.IsEnabled)
                {
                    TxtStatus.Text = "Cliente actualizado."; // O "Listo para jugar."
                }
                else if (TxtStatus.Text.Contains("Checking") || TxtStatus.Text.Contains("Initializing"))
                {
                    TxtStatus.Text = "Inicializando...";
                }
            }
            else
            {
                BtnEng.Foreground = Brushes.White;
                BtnEsp.Foreground = Brushes.Gray;
                if (TxtSubtitle != null) TxtSubtitle.Text = "";
                if (TxtResolutionLabel != null) TxtResolutionLabel.Text = "RESOLUTION:";
                if (TxtFullscreenLabel != null) TxtFullscreenLabel.Text = "FULLSCREEN";
                BtnPlay.Content = "PLAY";

                // LÓGICA CORREGIDA PARA EL STATUS
                if (BtnPlay.IsEnabled)
                {
                    TxtStatus.Text = "Client is up to date."; // O "Ready to play."
                }
                else if (TxtStatus.Text.Contains("Comprobando") || TxtStatus.Text.Contains("Inicializando"))
                {
                    TxtStatus.Text = "Initializing...";
                }
            }
        }

        private string CalculateMD5(string filename)
        {
            try
            {
                using (var md5 = MD5.Create())
                {
                    using (var stream = File.OpenRead(filename))
                    {
                        return BitConverter.ToString(md5.ComputeHash(stream)).Replace("-", "").ToLowerInvariant();
                    }
                }
            }
            catch { return ""; }
        }

        private void BtnMinimize_Click(object sender, RoutedEventArgs e) => this.WindowState = WindowState.Minimized;
        private void BtnClose_Click(object sender, RoutedEventArgs e) => Application.Current.Shutdown();
        private void Window_MouseDown(object sender, MouseButtonEventArgs e)
        {
            if (e.ChangedButton == MouseButton.Left) this.DragMove();
        }

        private void LoadCustomConfig()
        {
            try
            {
                if (File.Exists(_configPath))
                {
                    string json = File.ReadAllText(_configPath);
                    var config = JsonConvert.DeserializeObject<LauncherConfig>(json);
                    if (config != null)
                    {
                        this.Width = config.Width;
                        this.Height = config.Height;
                        this._isSpanish = config.IsSpanish;
                    }
                }
            }
            catch { }
        }

        private void LoadGameSettings()
        {
            try
            {
                // Load resolution from Settings.cfg (legacy)
                if (File.Exists(_gameSettingsPath))
                {
                    string[] lines = File.ReadAllLines(_gameSettingsPath);
                    foreach (string line in lines)
                    {
                        if (line.TrimStart().StartsWith("Resolution", StringComparison.OrdinalIgnoreCase))
                        {
                            string[] parts = line.Split('=');
                            if (parts.Length >= 2 && int.TryParse(parts[1].Trim(), out int res))
                            {
                                _selectedResolution = Math.Clamp(res, 0, 2);
                            }
                            break;
                        }
                    }
                }

                // Load fullscreen from GameConfig.ini (takes priority if exists)
                if (File.Exists(_gameConfigPath))
                {
                    string[] lines = File.ReadAllLines(_gameConfigPath);
                    foreach (string line in lines)
                    {
                        string trimmed = line.Trim();
                        if (trimmed.StartsWith(";") || trimmed.StartsWith("#") || string.IsNullOrEmpty(trimmed))
                            continue;

                        int eqIndex = trimmed.IndexOf('=');
                        if (eqIndex <= 0) continue;

                        string key = trimmed.Substring(0, eqIndex).Trim();
                        string value = trimmed.Substring(eqIndex + 1).Trim();

                        if (key.Equals("Resolution", StringComparison.OrdinalIgnoreCase))
                        {
                            if (int.TryParse(value, out int res))
                            {
                                _selectedResolution = Math.Clamp(res, 0, 2);
                            }
                        }
                        else if (key.Equals("Fullscreen", StringComparison.OrdinalIgnoreCase))
                        {
                            _selectedFullscreen = value == "1";
                        }
                    }
                }

                // Set UI controls
                if (CmbResolution != null && CmbResolution.Items.Count > _selectedResolution)
                {
                    CmbResolution.SelectedIndex = _selectedResolution;
                }
                if (ChkFullscreen != null)
                {
                    ChkFullscreen.IsChecked = _selectedFullscreen;
                }
            }
            catch { }
        }

        private void SaveGameSettings()
        {
            try
            {
                // Ensure DATA folder exists
                string dataFolder = Path.GetDirectoryName(_gameSettingsPath);
                if (!string.IsNullOrEmpty(dataFolder))
                {
                    Directory.CreateDirectory(dataFolder);
                }

                // Read existing settings or create default
                var settings = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase);
                
                if (File.Exists(_gameSettingsPath))
                {
                    string[] lines = File.ReadAllLines(_gameSettingsPath);
                    foreach (string line in lines)
                    {
                        int eqIndex = line.IndexOf('=');
                        if (eqIndex > 0)
                        {
                            string key = line.Substring(0, eqIndex).Trim();
                            string value = line.Substring(eqIndex + 1).Trim();
                            settings[key] = value;
                        }
                    }
                }
                else
                {
                    // Create default settings matching the game's defaults
                    settings["SettingM"] = "1";
                    settings["GuideMap"] = "0";
                    settings["Zoom"] = "1";
                    settings["AutoSS"] = "0";
                    settings["Sound"] = "1";
                    settings["Music"] = "1";
                    settings["SoundLevel"] = "100";
                    settings["MusicLevel"] = "100";
                    settings["Shout"] = "1";
                    settings["Whisper"] = "1";
                    settings["BarType"] = "0";
                    settings["ShowGrid"] = "0";
                    settings["GridColor"] = "0";
                    settings["GridTransparency"] = "0";
                    settings["Transparency"] = "0";
                    settings["Detail"] = "2";
                    settings["Item-Grounds"] = "0";
                    settings["Windows-Key"] = "1";
                    settings["Glares"] = "0";
                    settings["Stars"] = "0";
                    settings["Shadows"] = "0";
                    settings["Colors"] = "0";
                    settings["Trees"] = "0";
                    settings["Steeps"] = "0";
                    settings["Afks"] = "0";
                    settings["RedSteeps"] = "0";
                    settings["BarraNpc"] = "0";
                    settings["LowSprites"] = "0";
                    settings["Roofs"] = "0";
                    settings["TransBag"] = "0";
                    settings["StaggerDmg"] = "0";
                    settings["CandySize"] = "0";
                    settings["ShowShin"] = "0";
                    settings["ModernMouse"] = "1";
                }

                // Update resolution in Settings.cfg
                settings["Resolution"] = _selectedResolution.ToString();

                // Write Settings.cfg
                var sb = new System.Text.StringBuilder();
                foreach (var kvp in settings)
                {
                    sb.AppendLine($"{kvp.Key} = {kvp.Value}");
                }
                File.WriteAllText(_gameSettingsPath, sb.ToString());

                // Write GameConfig.ini for the client to read fullscreen and resolution settings
                // This file is read by the game client's LoadGameConfig() function
                var gameConfigSb = new System.Text.StringBuilder();
                gameConfigSb.AppendLine("; GameConfig.ini - Generated by Apocalypse Launcher");
                gameConfigSb.AppendLine("; Resolution: 0=1024x768, 1=800x600, 2=640x480");
                gameConfigSb.AppendLine("; Fullscreen: 0=Windowed, 1=Fullscreen (stretched to fill screen)");
                gameConfigSb.AppendLine();
                gameConfigSb.AppendLine($"Resolution = {_selectedResolution}");
                gameConfigSb.AppendLine($"Fullscreen = {(_selectedFullscreen ? "1" : "0")}");
                File.WriteAllText(_gameConfigPath, gameConfigSb.ToString());
            }
            catch { }
        }

        private void CmbResolution_SelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            if (CmbResolution?.SelectedItem is ComboBoxItem item && item.Tag is string tagStr)
            {
                if (int.TryParse(tagStr, out int res))
                {
                    _selectedResolution = res;
                }
            }
        }

        private void ChkFullscreen_Changed(object sender, RoutedEventArgs e)
        {
            _selectedFullscreen = ChkFullscreen?.IsChecked == true;
        }

        private void TxtFullscreenLabel_Click(object sender, MouseButtonEventArgs e)
        {
            // Toggle the checkbox when clicking on the label text
            if (ChkFullscreen != null)
            {
                ChkFullscreen.IsChecked = !ChkFullscreen.IsChecked;
            }
        }

        private void Window_Closing(object sender, System.ComponentModel.CancelEventArgs e)
        {
            try
            {
                _hotUpdateTimer?.Stop();
                _serverStatusTimer?.Stop();
                SaveWindowPlacementToSettings();
                ScheduleApplyPendingUpdatesOnExitIfNeeded();
                var config = new LauncherConfig
                {
                    Width = this.Width,
                    Height = this.Height,
                    IsSpanish = this._isSpanish
                };
                string json = JsonConvert.SerializeObject(config, Formatting.Indented);
                File.WriteAllText(_configPath, json);
            }
            catch { }
        }
    }
}