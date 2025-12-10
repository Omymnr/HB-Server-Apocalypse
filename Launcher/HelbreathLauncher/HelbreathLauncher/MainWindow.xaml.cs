using System;
using System.Diagnostics;
using System.IO;
using System.Net.Http;
using System.Net.Sockets;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Collections.Generic;

namespace HelbreathLauncher
{
    public partial class MainWindow : Window
    {
        // ==========================================
        // CONFIGURACIÓN DE TU SERVIDOR
        // ==========================================
        private const string RELEASE_IP = "89.7.69.125"; // Release Server (Public IP)
        private const string TEST_IP = "192.168.0.15";   // Test Server (LAN IP)

        private const int GAME_PORT = 2500;   // Puerto para jugar
        private const int WEB_PORT = 8888;    // Puerto para registrar cuentas (Web)

        private string currentIp = RELEASE_IP;
        private bool isCheckingStatus = true;

        public MainWindow()
        {
            InitializeComponent();
            StartServerPing(); // Inicia el chequeo de estado
            InitializeUpdater(); // Inicia Auto-Updater
        }

        // Mover la ventana (porque no tiene bordes)
        private void Window_MouseDown(object sender, MouseButtonEventArgs e)
        {
            if (e.ChangedButton == MouseButton.Left)
                this.DragMove();
        }

        private void BtnMinimize_Click(object sender, RoutedEventArgs e) => WindowState = WindowState.Minimized;
        private void BtnClose_Click(object sender, RoutedEventArgs e) => Close();

        // Cambio de servidor en el ComboBox
        private void CmbServer_SelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            if (CmbServer.SelectedIndex == 0)
                currentIp = RELEASE_IP;
            else
                currentIp = TEST_IP;

            // Actualizar estado inmediatamente al cambiar
            _ = UpdateServerStatus();
        }

        // Bucle infinito que comprueba el estado cada 5 segundos
        private async void StartServerPing()
        {
            while (isCheckingStatus)
            {
                await UpdateServerStatus();
                await Task.Delay(5000);
            }
        }

        // Función que hace PING al puerto 9907
        private async Task UpdateServerStatus()
        {
            bool isOnline = false;
            try
            {
                using (var client = new TcpClient())
                {
                    var connectTask = client.ConnectAsync(currentIp, GAME_PORT);
                    var timeoutTask = Task.Delay(1500); // 1.5 segundos de espera máxima

                    if (await Task.WhenAny(connectTask, timeoutTask) == connectTask && client.Connected)
                    {
                        isOnline = true;
                    }
                }
            }
            catch { isOnline = false; }

            // Actualizar colores en la interfaz
            if (isOnline)
            {
                StatusLight.Fill = new SolidColorBrush(Color.FromRgb(100, 255, 100)); // Verde
                TxtStatus.Text = "ONLINE";
            }
            else
            {
                StatusLight.Fill = new SolidColorBrush(Color.FromRgb(255, 80, 80));   // Rojo
                TxtStatus.Text = "OFFLINE";
            }
        }

        // ==========================================
        // BOTÓN JUGAR (Game.exe)
        // ==========================================
        private async void BtnPlay_Click(object sender, RoutedEventArgs e)
        {
            string gameExePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Game.exe");

            if (!File.Exists(gameExePath))
            {
                MessageBox.Show("¡Error!\nNo se encuentra 'Game.exe'.\nAsegúrate de poner este Launcher en la carpeta del juego.", "Falta Archivo", MessageBoxButton.OK, MessageBoxImage.Error);
                return;
            }

            try
            {
                // 1. Configurar argumentos de lanzamiento (IP y Puerto)
                ProcessStartInfo psi = new ProcessStartInfo();
                psi.Arguments = $"{currentIp} {GAME_PORT}";

                // 2. Ejecutar el juego
                // MessageBox.Show($"Launching Game.exe from: {gameExePath} with args: {psi.Arguments}"); // Debug visual removed
                psi.FileName = gameExePath;
                psi.WorkingDirectory = AppDomain.CurrentDomain.BaseDirectory;
                psi.UseShellExecute = true; // Importante para ejecutables antiguos
                Process.Start(psi);

                // 3. No es necesario esperar ni borrar archivo
                await Task.Delay(1000);
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error al iniciar el juego: " + ex.Message);
            }
        }

        // ==========================================
        // SISTEMA DE REGISTRO (Web API 8888)
        // ==========================================
        private void BtnRegister_Click(object sender, RoutedEventArgs e)
        {
            OverlayRegister.Visibility = Visibility.Visible;
            TxtRegStatus.Text = "";
        }

        private void BtnCloseRegister_Click(object sender, RoutedEventArgs e)
        {
            OverlayRegister.Visibility = Visibility.Collapsed;
        }

        private async void BtnSubmitRegister_Click(object sender, RoutedEventArgs e)
        {
            string user = TxtRegUser.Text.Trim();
            string pass = TxtRegPass.Password.Trim();
            string email = TxtRegEmail.Text.Trim();

            if (user.Length < 3 || pass.Length < 3)
            {
                TxtRegStatus.Text = "El usuario y contraseña deben tener al menos 3 caracteres.";
                return;
            }

            TxtRegStatus.Text = "Conectando con el servidor...";
            BtnSubmitRegister.IsEnabled = false; // Evitar doble click

            try
            {
                using (HttpClient client = new HttpClient())
                {
                    // URL con puerto 8888
                    string url = $"http://{RELEASE_IP}:{WEB_PORT}/api/register.php";

                    var datos = new FormUrlEncodedContent(new[]
                    {
                        new KeyValuePair<string, string>("account", user),
                        new KeyValuePair<string, string>("password", pass),
                        new KeyValuePair<string, string>("email", email)
                    });

                    // Enviar petición POST
                    HttpResponseMessage response = await client.PostAsync(url, datos);

                    // Leer respuesta
                    string resultado = await response.Content.ReadAsStringAsync();

                    if (resultado.Contains("SUCCESS"))
                    {
                        MessageBox.Show("¡Cuenta creada con éxito!\nYa puedes entrar al juego.", "Bienvenido", MessageBoxButton.OK, MessageBoxImage.Information);
                        OverlayRegister.Visibility = Visibility.Collapsed;
                        // Limpiar campos
                        TxtRegUser.Text = "";
                        TxtRegPass.Password = "";
                        TxtRegEmail.Text = "";
                    }
                    else
                    {
                        TxtRegStatus.Text = "Error del servidor: " + resultado;
                    }
                }
            }
            catch (Exception ex)
            {
                TxtRegStatus.Text = "No se pudo conectar al servidor de registro (Puerto 8888 cerrado o IP inaccesible).";
            }
            finally
            {
                BtnSubmitRegister.IsEnabled = true;
            }
        }
        // ==========================================
        // AUTO-UPDATER
        // ==========================================
        private UpdateManager _updater;
        private ProgressBar _progBar;
        private TextBlock _lblStatus;

        private void InitializeUpdater()
        {
            try 
            {
                // Create controls programmatically to verify preservation of XAML
                // We'll add them to the main Grid (row 2, where the status light is, or overlay at bottom)
                
                // Container for Updater UI (Bottom of the window)
                // Existing Grid has 3 rows. Row 2 is typically the status bar.
                // We will inject a StackPanel in Grid.Row="2" to sit on top or replace the status text temporarily.
                
                // Use the main grid from the XAML. The XAML root grid doesn't have a name, 
                // but we can assume 'Content' of the Window is the Border, and its Child is the Grid.
                // Let's rely on finding the Grid by type or structure for robustness, 
                // OR simpler: Add to the Grid where 'TxtStatus' lives.
                
                // TxtStatus is in a StackPanel, which is in a Border in Grid.Row="2".
                // We want to overlay or be next to it.
                
                // Better approach: Add a new Border at the bottom of the main Grid (Row 2).
                
                if (this.Content is Border mainBorder && mainBorder.Child is Grid mainGrid)
                {
                    var updaterPanel = new StackPanel
                    {
                        Orientation = Orientation.Vertical,
                        VerticalAlignment = VerticalAlignment.Bottom,
                        Margin = new Thickness(20, 0, 20, 35), // Just above bottom status
                        Visibility = Visibility.Collapsed // Hidden by default
                    };
                    Grid.SetRow(updaterPanel, 1); // Put in Middle row (Row 1) at the bottom, so it doesn't overlap status light
                    Grid.SetColumnSpan(updaterPanel, 2);

                    _lblStatus = new TextBlock
                    {
                        Text = "Iniciando actualizador...",
                        Foreground = Brushes.White,
                        FontSize = 12,
                        HorizontalAlignment = HorizontalAlignment.Center,
                        Margin = new Thickness(0, 0, 0, 5)
                    };

                    _progBar = new ProgressBar
                    {
                        Height = 10,
                        Width = 400,
                        Minimum = 0,
                        Maximum = 100,
                        Background = new SolidColorBrush(Color.FromRgb(30, 30, 30)),
                        Foreground = new SolidColorBrush(Color.FromRgb(0, 122, 204)), // VS Blue
                        BorderThickness = new Thickness(0)
                    };

                    updaterPanel.Children.Add(_lblStatus);
                    updaterPanel.Children.Add(_progBar);

                    mainGrid.Children.Add(updaterPanel);

                    // Initialize Manager
                    _updater = new UpdateManager(this, _progBar, _lblStatus);
                    
                    // Start Update Check
                    _ = _updater.CheckAndApplyUpdates();
                }
                else
                {
                    // Fallback or Log if structure isn't as expected (to avoid crash)
                    // MessageBox.Show("Warning: Could not inject Updater UI. XAML structure mismatch.");
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error initializing updater: " + ex.Message);
            }
        }
    }
}
