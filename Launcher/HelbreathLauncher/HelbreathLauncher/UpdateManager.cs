using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Net.Http;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;

using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Net.Http;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;

namespace HelbreathLauncher
{
    public class UpdateManager
    {
        // CONFIG
        private const string REPO_OWNER = "Omymnr";
        private const string REPO_NAME = "HB-Server-Apocalypse";
        private const string BRANCH = "main";
        
        // Base URL for RAW files (We will look inside 'Helbreath' folder in the repo)
        // EDITED: User wants manifest in Root, so Base URL is Root.
        private const string BASE_URL = $"https://raw.githubusercontent.com/{REPO_OWNER}/{REPO_NAME}/{BRANCH}/";
        
        private const string VERSION_FILE = "version.txt";
        private const string MANIFEST_FILE = "files.json";
        private const string LOCAL_VERSION_FILE = "version.dat";

        private readonly HttpClient _httpClient;
        private readonly string _basePath;
        private readonly ProgressBar _progressBar;
        private readonly TextBlock _statusLabel;
        private readonly Window _mainWindow;

        private readonly Button _playButton;

        private readonly TextBlock _versionLabel;
        private string _currentLang = "ES";

        public UpdateManager(Window window, ProgressBar bar, TextBlock label, Button playBtn, TextBlock versionLabel)
        {
            _mainWindow = window;
            _progressBar = bar;
            _statusLabel = label;
            _playButton = playBtn;
            _versionLabel = versionLabel;
            _basePath = AppDomain.CurrentDomain.BaseDirectory;
            _httpClient = new HttpClient();
            _httpClient.DefaultRequestHeaders.TryAddWithoutValidation("User-Agent", "HelbreathLauncher-Updater");
        }

        public void SetLanguage(string lang)
        {
            _currentLang = lang;
        }

        private string GetMsg(string key)
        {
            bool isEs = _currentLang == "ES";
            switch (key)
            {
                case "Checking": return isEs ? "Comprobando versión..." : "Checking version...";
                case "ErrorCheck": return isEs ? "Error comprobando versión." : "Error checking version.";
                case "Updated": return isEs ? "Cliente actualizado." : "Client updated.";
                case "NewVersion": return isEs ? "Nueva versión detectada" : "New version found";
                case "ErrorManifest": return isEs ? "Error al descargar lista." : "Error downloading file list.";
                case "EmptyManifest": return isEs ? "Lista vacía." : "Empty file list.";
                case "Verifying": return isEs ? "Verificando archivos" : "Verifying files";
                case "Verified": return isEs ? "Verificado. Actualizando..." : "Verified. Updating...";
                case "Downloading": return isEs ? "Descargando" : "Downloading";
                case "Finished": return isEs ? "Actualización completada." : "Update finished.";
                case "Error": return "Error: ";
                case "Vers": return isEs ? "Versión" : "Version";
                default: return key;
            }
        }

        public async Task CheckAndApplyUpdates()
        {
            try
            {
                SetPlayEnabled(false);
                UpdateStatus(GetMsg("Checking"), 0);

                // 1. Check Remote Version
                string remoteVersionStr = await DownloadString(VERSION_FILE);
                if (string.IsNullOrEmpty(remoteVersionStr))
                {
                    UpdateStatus(GetMsg("ErrorCheck"), 0);
                    await Task.Delay(2000);
                    if (File.Exists(Path.Combine(_basePath, "Game.exe")))
                    {
                         HideUI(); 
                         return; 
                    }
                    HideUI();
                    return;
                }

                // Parse as DOUBLE (Decimal version)
                if (!double.TryParse(remoteVersionStr.Trim(), System.Globalization.NumberStyles.Any, System.Globalization.CultureInfo.InvariantCulture, out double remoteVersion))
                {
                    remoteVersion = 0.0; 
                }

                // 2. Check Local Version
                double localVersion = GetLocalVersion();

                // Update Version Label immediately (current)
                UpdateVersionLabel(localVersion);

                // 3. Compare
                if (localVersion >= remoteVersion)
                {
                    UpdateStatus(GetMsg("Updated"), 100);
                    UpdateVersionLabel(localVersion); // Ensure correct
                    await Task.Delay(500);
                    HideUI();
                    SetPlayEnabled(true);
                    return;
                }

                UpdateStatus($"{GetMsg("NewVersion")} ({localVersion} -> {remoteVersion})...", 5);

                // 4. Download Manifest
                string manifestJson = await DownloadString(MANIFEST_FILE);
                if (string.IsNullOrEmpty(manifestJson))
                {
                    UpdateStatus(GetMsg("ErrorManifest"), 0);
                    await Task.Delay(2000);
                    HideUI();
                    return;
                }

                List<ManifestEntry> remoteFiles;
                try
                {
                    remoteFiles = JsonSerializer.Deserialize<List<ManifestEntry>>(manifestJson);
                }
                catch
                {
                    remoteFiles = new List<ManifestEntry>();
                }

                if (remoteFiles == null || remoteFiles.Count == 0)
                {
                    UpdateStatus(GetMsg("EmptyManifest"), 100);
                    SetLocalVersion(remoteVersion); 
                    await Task.Delay(1000);
                    HideUI();
                    SetPlayEnabled(true);
                    return;
                }

                // 5. Verify Files
                var filesToUpdate = new List<ManifestEntry>();
                int checkedCount = 0;
                
                await Task.Run(() =>
                {
                    foreach (var entry in remoteFiles)
                    {
                        checkedCount++;
                        if (checkedCount % 5 == 0)
                        {
                            UpdateStatus($"{GetMsg("Verifying")} ({checkedCount}/{remoteFiles.Count})...", 
                                5 + ((double)checkedCount / remoteFiles.Count * 20)); 
                        }

                        if (NeedsUpdate(entry))
                        {
                            filesToUpdate.Add(entry);
                        }
                    }
                });

                if (filesToUpdate.Count == 0)
                {
                    UpdateStatus($"{GetMsg("Vers")} {remoteVersion}", 100);
                    SetLocalVersion(remoteVersion);
                    UpdateVersionLabel(remoteVersion);
                    await Task.Delay(1000);
                    HideUI();
                    SetPlayEnabled(true);
                    return;
                }

                // 6. Download Updates
                int total = filesToUpdate.Count;
                int current = 0;
                bool selfUpdate = false;

                foreach (var file in filesToUpdate)
                {
                    current++;
                    double progress = 25 + ((double)current / total * 75); 
                    UpdateStatus($"{GetMsg("Downloading")} ({current}/{total}): {file.Path}", progress);

                    await DownloadFile(file);

                    if (file.Path.EndsWith("HelbreathLauncher.exe", StringComparison.OrdinalIgnoreCase))
                    {
                        selfUpdate = true;
                    }
                }

                // 7. Finish
                SetLocalVersion(remoteVersion);
                UpdateVersionLabel(remoteVersion);
                UpdateStatus($"{GetMsg("Finished")}", 100); 
                await Task.Delay(1000);

                if (selfUpdate)
                {
                    PerformSelfUpdateRestart();
                }
                else
                {
                    HideUI();
                    SetPlayEnabled(true);
                }

            }
            catch (Exception ex)
            {
                UpdateStatus($"{GetMsg("Error")} {ex.Message}", 0);
                await Task.Delay(3000);
                HideUI();
            }
        }
        
        private void UpdateVersionLabel(double v)
        {
             Application.Current.Dispatcher.Invoke(() =>
            {
                if (_versionLabel != null) 
                    _versionLabel.Text = $"{GetMsg("Vers")}: {v.ToString("0.0", System.Globalization.CultureInfo.InvariantCulture)}";
            });
        }
        
        private void SetPlayEnabled(bool enabled)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                if (_playButton != null) _playButton.IsEnabled = enabled;
            });
        }

        private async Task<string> DownloadString(string relativeUrl)
        {
            try
            {
                // Cache busting
                string url = BASE_URL + relativeUrl + "?t=" + DateTime.Now.Ticks;
                return await _httpClient.GetStringAsync(url);
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Failed to download {relativeUrl}: {ex.Message}");
                return null;
            }
        }

        // NeedsUpdate, ComputeSha256, DownloadFile kept same (except CleanPath fix is needed if I replace them?)
        // I am using "EndLine: 302" which covers DownloadString.
        // Wait, Need to make sure I don't lose NeedsUpdate etc.
        // The original code has NeedsUpdate starting around 214.
        // I will replace UP TO NeedsUpdate. 

        private double GetLocalVersion()
        {
            string path = Path.Combine(_basePath, LOCAL_VERSION_FILE);
            if (File.Exists(path))
            {
                try
                {
                    string txt = File.ReadAllText(path);
                    if (double.TryParse(txt.Trim(), System.Globalization.NumberStyles.Any, System.Globalization.CultureInfo.InvariantCulture, out double v)) return v;
                }
                catch { }
            }
            return 0.0; 
        }

        private void SetLocalVersion(double version)
        {
            try
            {
                string path = Path.Combine(_basePath, LOCAL_VERSION_FILE);
                File.WriteAllText(path, version.ToString("0.0", System.Globalization.CultureInfo.InvariantCulture));
            }
            catch { }
        }


        private bool NeedsUpdate(ManifestEntry entry)
        {
            // Fix: Use CleanPath
            string cleanPath = CleanPath(entry.Path);
            string localPath = Path.Combine(_basePath, cleanPath.Replace("/", "\\"));
            
            if (!File.Exists(localPath)) return true;

            // Size check first (fast)
            FileInfo info = new FileInfo(localPath);
            if (info.Length != entry.Size) return true;

            // Hash check (slower but accurate)
            string localHash = ComputeSha256(localPath);
            return !string.Equals(localHash, entry.Hash, StringComparison.OrdinalIgnoreCase);
        }

        private string ComputeSha256(string filePath)
        {
            try
            {
                using (var stream = File.OpenRead(filePath))
                using (var sha = SHA256.Create())
                {
                    byte[] hash = sha.ComputeHash(stream);
                    return BitConverter.ToString(hash).Replace("-", "").ToLowerInvariant();
                }
            }
            catch
            {
                return "";
            }
        }

        private async Task DownloadFile(ManifestEntry entry)
        {
            // Fix: Use CleanPath
            string cleanPath = CleanPath(entry.Path);
            string localPath = Path.Combine(_basePath, cleanPath.Replace("/", "\\"));
            string url = BASE_URL + entry.Path; // Raw file URL (Needs full repo path)

            string dir = Path.GetDirectoryName(localPath);
            if (!Directory.Exists(dir)) Directory.CreateDirectory(dir);

            // Handle Self-Update specially
            bool isSelfUpdate = cleanPath.EndsWith("HelbreathLauncher.exe", StringComparison.OrdinalIgnoreCase);
            if (isSelfUpdate) localPath += ".tmp";

            using (var response = await _httpClient.GetAsync(url))
            {
                response.EnsureSuccessStatusCode();
                using (var fs = new FileStream(localPath, FileMode.Create, FileAccess.Write, FileShare.None))
                {
                    await response.Content.CopyToAsync(fs);
                }
            }
        }



        private void PerformSelfUpdateRestart()
        {
            string currentExe = Process.GetCurrentProcess().MainModule.FileName;
            string newExe = currentExe + ".tmp";
            string oldExe = currentExe + ".old";

            try
            {
                // We leave the logic to the OS/Launcher restart.
                // Simple atomic move if possible.
                if (File.Exists(oldExe)) File.Delete(oldExe);
                
                if (File.Exists(newExe))
                {
                    File.Move(currentExe, oldExe);
                    File.Move(newExe, currentExe);
                    
                    MessageBox.Show("Actualización de Launcher completada. Reiniciando...", "Helbreath", MessageBoxButton.OK, MessageBoxImage.Information);
                    Process.Start(currentExe);
                    Application.Current.Shutdown();
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error al actualizar Launcher (Permisos?): {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        private void UpdateStatus(string text, double percent)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                _statusLabel.Text = text;
                _progressBar.Value = percent;
                if (_progressBar.Visibility != Visibility.Visible)
                {
                    _progressBar.Visibility = Visibility.Visible;
                    _statusLabel.Visibility = Visibility.Visible;
                }
            });
        }

        private void HideUI()
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                _progressBar.Visibility = Visibility.Collapsed;
                _statusLabel.Visibility = Visibility.Collapsed;
            });
        }

        private string CleanPath(string repoPath)
        {
            // If the repo path starts with "Helbreath/", we strip it because 
            // the Launcher is running INSIDE that folder.
            if (repoPath.StartsWith("Helbreath/", StringComparison.OrdinalIgnoreCase))
            {
                return repoPath.Substring("Helbreath/".Length);
            }
            return repoPath;
        }
    }

    public class ManifestEntry
    {
        public string Path { get; set; }
        public string Hash { get; set; } // SHA256
        public long Size { get; set; }
    }
}
