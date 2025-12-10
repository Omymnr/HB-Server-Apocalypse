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
        private const string REPO_OWNER = "Omymnr";
        private const string REPO_NAME = "HB-Server-Apocalypse";
        private const string BRANCH = "main";
        private const string API_TREE_URL = $"https://api.github.com/repos/{REPO_OWNER}/{REPO_NAME}/git/trees/{BRANCH}?recursive=1";
        private const string RAW_BASE_URL = $"https://raw.githubusercontent.com/{REPO_OWNER}/{REPO_NAME}/{BRANCH}/";
        private const string CACHE_FILE = "release_cache.json";

        private static readonly HashSet<string> TARGET_FOLDERS = new HashSet<string>(StringComparer.OrdinalIgnoreCase)
        {
            "CONTENTS", "SPRITES", "SOUNDS", "MAPDATA", "MUSIC", "FONTS", "RENDER"
        };
        
        private static readonly HashSet<string> TARGET_FILES = new HashSet<string>(StringComparer.OrdinalIgnoreCase)
        {
            "Game.exe", "HelbreathLauncher.exe" 
        };

        private readonly HttpClient _httpClient;
        private readonly string _basePath;
        private readonly ProgressBar _progressBar;
        private readonly TextBlock _statusLabel;
        private readonly Window _mainWindow;
        
        // Local Cache: Path -> { LastWriteTime, SHA1 }
        private Dictionary<string, FileCacheEntry> _fileCache;

        public UpdateManager(Window window, ProgressBar bar, TextBlock label)
        {
            _mainWindow = window;
            _progressBar = bar;
            _statusLabel = label;
            _basePath = AppDomain.CurrentDomain.BaseDirectory;
            _httpClient = new HttpClient();
            _httpClient.DefaultRequestHeaders.Add("User-Agent", "HelbreathLauncher-Updater");
            _fileCache = LoadCache();
        }

        public async Task CheckAndApplyUpdates()
        {
            try
            {
                UpdateStatus("Conectando con el servidor...", 0);
                
                var remoteFiles = await GetGitHubFiles();
                if (remoteFiles == null) 
                {
                    UpdateStatus("Error de conexión. Iniciando...", 100);
                    await Task.Delay(2000);
                    HideUI();
                    return;
                }

                // Filter files
                var filesToUpdate = new List<GitHubFile>();
                var targetFiles = remoteFiles.Where(f => f.type == "blob" && IsTargetFile(f.path)).ToList();

                int checkedCount = 0;
                int totalToCheck = targetFiles.Count;

                // Check dependencies (Calculate hashes if needed)
                // We use Task.Run for heavy hashing to not freeze UI, but here we do it sequentially or batched
                await Task.Run(() => 
                {
                    foreach (var file in targetFiles)
                    {
                        checkedCount++;
                        // Report checking progress if it takes long (e.g. cold start)
                        if (totalToCheck > 100 && checkedCount % 10 == 0)
                        {
                            UpdateStatus($"Verificando archivos ({checkedCount}/{totalToCheck})...", (double)checkedCount/totalToCheck * 50); // First 50% is checking
                        }

                        if (NeedsUpdate(file))
                        {
                            filesToUpdate.Add(file);
                        }
                    }
                });

                if (filesToUpdate.Count == 0)
                {
                    UpdateStatus("Cliente actualizado.", 100);
                    SaveCache(); // Save any new hashes computed during check
                    await Task.Delay(500);
                    HideUI();
                    return;
                }

                // Download Phase
                int total = filesToUpdate.Count;
                int current = 0;

                foreach (var file in filesToUpdate)
                {
                    current++;
                    double progress = 50 + ((double)current / total * 50); // Second 50% is downloading
                    string cleanDisplayPath = CleanPath(file.path);
                    UpdateStatus($"Descargando ({current}/{total}): {cleanDisplayPath}", progress);

                    await DownloadFile(file);
                    
                    // Update cache for the new file immediately
                    string cleanPath = CleanPath(file.path);
                    string localPath = Path.Combine(_basePath, cleanPath.Replace("/", "\\"));
                    
                    if (File.Exists(localPath))
                    {
                        var info = new FileInfo(localPath);
                        _fileCache[file.path] = new FileCacheEntry 
                        { 
                            LastWriteTime = info.LastWriteTimeUtc.Ticks,
                            Hash = file.sha // We trust the downloaded SHA matches
                        };
                    }
                }

                SaveCache(); // Final save
                UpdateStatus("Actualización completada.", 100);
                await Task.Delay(1000);
                
                // Check if we updated the launcher itself
                if (filesToUpdate.Any(f => CleanPath(f.path).EndsWith("HelbreathLauncher.exe", StringComparison.OrdinalIgnoreCase)))
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

        private bool NeedsUpdate(GitHubFile file)
        {
            string cleanPath = CleanPath(file.path);
            string localPath = Path.Combine(_basePath, cleanPath.Replace("/", "\\"));
            if (!File.Exists(localPath)) return true;

            FileInfo info = new FileInfo(localPath);
            
            // Check Cache first
            if (_fileCache.TryGetValue(file.path, out var cachedEntry))
            {
                // Verify timestamp has not changed
                if (info.LastWriteTimeUtc.Ticks == cachedEntry.LastWriteTime)
                {
                    // Timestamp matches, we trust the cached hash
                    return cachedEntry.Hash != file.sha;
                }
            }

            // Cache miss or modified file: Compute real SHA1
            // This is the "slow" part, but only happens once or on modified files
            string currentHash = ComputeGitSha1(localPath);

            // Update cache memory (will be saved later)
            _fileCache[file.path] = new FileCacheEntry 
            { 
                LastWriteTime = info.LastWriteTimeUtc.Ticks, 
                Hash = currentHash 
            };

            return currentHash != file.sha;
        }

        private string ComputeGitSha1(string filePath)
        {
            try
            {
                byte[] content = File.ReadAllBytes(filePath);
                
                // Git header: "blob {size}\0"
                string header = $"blob {content.Length}\0";
                byte[] headerBytes = Encoding.ASCII.GetBytes(header);

                // Combine header + content
                byte[] combined = new byte[headerBytes.Length + content.Length];
                Buffer.BlockCopy(headerBytes, 0, combined, 0, headerBytes.Length);
                Buffer.BlockCopy(content, 0, combined, headerBytes.Length, content.Length);

                using (var sha1 = SHA1.Create())
                {
                    byte[] hashBytes = sha1.ComputeHash(combined);
                    return BitConverter.ToString(hashBytes).Replace("-", "").ToLowerInvariant();
                }
            }
            catch
            {
                return ""; // Error reading file -> Force update
            }
        }

        private Dictionary<string, FileCacheEntry> LoadCache()
        {
            try
            {
                string cachePath = Path.Combine(_basePath, CACHE_FILE);
                if (File.Exists(cachePath))
                {
                    string json = File.ReadAllText(cachePath);
                    var cache = JsonSerializer.Deserialize<Dictionary<string, FileCacheEntry>>(json);
                    return cache ?? new Dictionary<string, FileCacheEntry>();
                }
            }
            catch { /* Ignore corrupted cache */ }
            return new Dictionary<string, FileCacheEntry>();
        }

        private void SaveCache()
        {
            try
            {
                string cachePath = Path.Combine(_basePath, CACHE_FILE);
                string json = JsonSerializer.Serialize(_fileCache);
                File.WriteAllText(cachePath, json);
            }
            catch { /* Ignore save error */ }
        }

        private async Task DownloadFile(GitHubFile file)
        {
            string cleanPath = CleanPath(file.path);
            string localPath = Path.Combine(_basePath, cleanPath.Replace("/", "\\"));
            string rawUrl = RAW_BASE_URL + file.path; // RAW URL needs full path

            string dir = Path.GetDirectoryName(localPath);
            if (!Directory.Exists(dir)) Directory.CreateDirectory(dir);

            bool isSelfUpdate = cleanPath.EndsWith("HelbreathLauncher.exe", StringComparison.OrdinalIgnoreCase);
            if (isSelfUpdate) localPath += ".tmp";

            using (var response = await _httpClient.GetAsync(rawUrl))
            {
                response.EnsureSuccessStatusCode();
                using (var fs = new FileStream(localPath, FileMode.Create, FileAccess.Write, FileShare.None))
                {
                    await response.Content.CopyToAsync(fs);
                }
            }
            
            // Fix timestamp
            if (!isSelfUpdate)
            {
                 File.SetLastWriteTimeUtc(localPath, DateTime.UtcNow);
            }
        }
        
        private void PerformSelfUpdateRestart()
        {
            string currentExe = Process.GetCurrentProcess().MainModule.FileName;
            string newExe = currentExe + ".tmp";
            string oldExe = currentExe + ".old";

            try
            {
                if (File.Exists(oldExe)) File.Delete(oldExe);
                if (File.Exists(newExe))
                {
                    File.Move(currentExe, oldExe);
                    File.Move(newExe, currentExe);

                    MessageBox.Show("Actualización recibida. Reiniciando...", "Helbreath Update", MessageBoxButton.OK, MessageBoxImage.Information);

                    Process.Start(currentExe);
                    Application.Current.Shutdown();
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error actualizando Launcher: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        // Helpers
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

        private bool IsTargetFile(string path)
        {
            string cleanPath = CleanPath(path);
            cleanPath = cleanPath.Replace("/", "\\");
            
            string fileName = Path.GetFileName(cleanPath);
            if (TARGET_FILES.Contains(fileName)) return true;
            
            string[] parts = cleanPath.Split('\\');
            if (parts.Length > 0 && TARGET_FOLDERS.Contains(parts[0])) return true;
            
            return false;
        }

        private async Task<List<GitHubFile>> GetGitHubFiles()
        {
            try
            {
                string json = await _httpClient.GetStringAsync(API_TREE_URL);
                var treeResponse = JsonSerializer.Deserialize<GitHubTreeResponse>(json);
                return treeResponse?.tree;
            }
            catch (Exception ex)
            {
                Debug.WriteLine("GitHub API Error: " + ex.Message);
                return null;
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

    public class GitHubTreeResponse
    {
        public List<GitHubFile> tree { get; set; }
    }

    public class GitHubFile
    {
        public string path { get; set; }
        public string type { get; set; }
        public string sha { get; set; }
        public long size { get; set; }
    }
    
    public class FileCacheEntry
    {
        public long LastWriteTime { get; set; }
        public string Hash { get; set; }
    }
}
