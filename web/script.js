/* --- script.js --- */
function toggleLanguage() {
    const body = document.body;
    if (body.classList.contains('show-en')) {
        body.classList.replace('show-en', 'show-es');
        localStorage.setItem('hb-lang', 'es');
    } else {
        body.classList.replace('show-es', 'show-en');
        localStorage.setItem('hb-lang', 'en');
    }
}

// Ejecutar al cargar cada página
window.onload = function() {
    const savedLang = localStorage.getItem('hb-lang');
    // Si hay un idioma guardado, lo aplicamos, si no, defecto inglés
    if (savedLang === 'es') {
        document.body.classList.replace('show-en', 'show-es');
    } else {
        // Asegurar que siempre empiece limpio si no hay nada guardado
        if (!document.body.classList.contains('show-en') && !document.body.classList.contains('show-es')) {
             document.body.classList.add('show-en');
        }
    }
}