function insertTag(tag, param = '') {
    const textarea = document.querySelector('textarea');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const selectedText = text.substring(start, end);
    
    let replacement = '';
    
    if (param) {
        replacement = `[${tag}=${param}]${selectedText}[/${tag}]`;
    } else {
        replacement = `[${tag}]${selectedText}[/${tag}]`;
    }

    textarea.value = text.substring(0, start) + replacement + text.substring(end);
}

function insertColor() {
    const color = document.getElementById('colorPicker').value;
    insertTag('color', color);
}