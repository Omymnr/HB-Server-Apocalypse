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
        private const string BASE_URL = $"https://raw.githubusercontent.com/{REPO_OWNER}/{REPO_NAME}/{BRANCH}/Helbreath/";
        
        private const string VERSION_FILE = "version.txt";
        private const string MANIFEST_FILE = "files.json";
        private const string LOCAL_VERSION_FILE = "version.dat";

        private readonly HttpClient _httpClient;
        private readonly string _basePath;
        private readonly ProgressBar _progressBar;
        private readonly TextBlock _statusLabel;
        private readonly Window _mainWindow;

        public UpdateManager(Window window, ProgressBar bar, TextBlock label)
        {
            _mainWindow = window;
            _progressBar = bar;
            _statusLabel = label;
            _basePath = AppDomain.CurrentDomain.BaseDirectory;
            _httpClient = new HttpClient();
            // No User-Agent needed for Raw content usually, but good practice
            _httpClient.DefaultRequestHeaders.TryAddWithoutValidation("User-Agent", "HelbreathLauncher-Updater");
        }

        public async Task CheckAndApplyUpdates()
        {
            try
            {
                UpdateStatus("Comprobando versión...", 0);

                // 1. Check Remote Version
                string remoteVersionStr = await DownloadString(VERSION_FILE);
                if (string.IsNullOrEmpty(remoteVersionStr))
                {
                    UpdateStatus("Error comprobando versión.", 0);
                    await Task.Delay(2000);
                    HideUI();
                    return;
                }

                if (!int.TryParse(remoteVersionStr.Trim(), out int remoteVersion))
                {
                    // If version.txt is not an int, maybe it's empty or text. Assume 0.
                    remoteVersion = 0; 
                }

                // 2. Check Local Version
                int localVersion = GetLocalVersion();

                // 3. Compare
                if (localVersion >= remoteVersion)
                {
                    UpdateStatus("Cliente actualizado.", 100);
                    await Task.Delay(1000);
                    HideUI();
                    return;
                }

                UpdateStatus($"Nueva versión detectada ({localVersion} -> {remoteVersion})...", 5);

                // 4. Download Manifest
                string manifestJson = await DownloadString(MANIFEST_FILE);
                if (string.IsNullOrEmpty(manifestJson))
                {
                    UpdateStatus("Error descargando lista de archivos.", 0);
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
                    UpdateStatus("Lista de archivos vacía.", 100);
                    SetLocalVersion(remoteVersion); // Assume updated if empty? Or error.
                    await Task.Delay(1000);
                    HideUI();
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
                            UpdateStatus($"Verificando archivos ({checkedCount}/{remoteFiles.Count})...", 
                                5 + ((double)checkedCount / remoteFiles.Count * 20)); // 5% -> 25%
                        }

                        if (NeedsUpdate(entry))
                        {
                            filesToUpdate.Add(entry);
                        }
                    }
                });

                if (filesToUpdate.Count == 0)
                {
                    UpdateStatus("Archivos verificados. Actualizando versión...", 100);
                    SetLocalVersion(remoteVersion);
                    await Task.Delay(1000);
                    HideUI();
                    return;
                }

                // 6. Download Updates
                int total = filesToUpdate.Count;
                int current = 0;
                bool selfUpdate = false;

                foreach (var file in filesToUpdate)
                {
                    current++;
                    double progress = 25 + ((double)current / total * 75); // 25% -> 100%
                    UpdateStatus($"Descargando ({current}/{total}): {file.Path}", progress);

                    await DownloadFile(file);

                    if (file.Path.EndsWith("HelbreathLauncher.exe", StringComparison.OrdinalIgnoreCase))
                    {
                        selfUpdate = true;
                    }
                }

                // 7. Finish
                SetLocalVersion(remoteVersion);
                UpdateStatus("Actualización completada.", 100);
                await Task.Delay(1000);

                if (selfUpdate)
                {
                    PerformSelfUpdateRestart();
                }
                else
                {
                    HideUI();
                }

            }
            catch (Exception ex)
            {
                UpdateStatus($"Error: {ex.Message}", 0);
                await Task.Delay(3000);
                HideUI();
            }
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

        private bool NeedsUpdate(ManifestEntry entry)
        {
            string localPath = Path.Combine(_basePath, entry.Path.Replace("/", "\\"));
            
            if (!File.Exists(localPath)) return true;

            // Size check first (fast)
            FileInfo info = new FileInfo(localPath);
            if (info.Length != entry.Size) return true;

            // Hash check (slower but accurate)
            // Only strictly needed if size matches but content changed. 
            // For now, let's assume if size matches it's OK to save time, 
            // OR fully check hash if you want 100% safety.
            // Let's do Hash check to be safe.
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
            string localPath = Path.Combine(_basePath, entry.Path.Replace("/", "\\"));
            string url = BASE_URL + entry.Path; // Raw file URL

            string dir = Path.GetDirectoryName(localPath);
            if (!Directory.Exists(dir)) Directory.CreateDirectory(dir);

            // Handle Self-Update specially
            bool isSelfUpdate = entry.Path.EndsWith("HelbreathLauncher.exe", StringComparison.OrdinalIgnoreCase);
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

        private int GetLocalVersion()
        {
            string path = Path.Combine(_basePath, LOCAL_VERSION_FILE);
            if (File.Exists(path))
            {
                try
                {
                    string txt = File.ReadAllText(path);
                    if (int.TryParse(txt.Trim(), out int v)) return v;
                }
                catch { }
            }
            return 0; // Default if no file
        }

        private void SetLocalVersion(int version)
        {
            try
            {
                string path = Path.Combine(_basePath, LOCAL_VERSION_FILE);
                File.WriteAllText(path, version.ToString());
            }
            catch { }
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
    }

    public class ManifestEntry
    {
        public string Path { get; set; }
        public string Hash { get; set; } // SHA256
        public long Size { get; set; }
    }
}
