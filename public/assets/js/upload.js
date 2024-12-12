document.getElementById('uploadForm').addEventListener('submit', function (e) {
    const fileInput = document.getElementById('image_file');
    const file = fileInput.files[0];
    const caption = document.getElementById('caption').value;

    if (!file) {
        alert('Please select an image to upload.');
        e.preventDefault();
        return;
    }

    const minSize = 10 * 1024; // 10KB
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size < minSize || file.size > maxSize) {
        alert('File size must be between 10KB and 5MB.');
        e.preventDefault();
        return;
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        alert('Only JPG, PNG, and GIF formats are allowed.');
        e.preventDefault();
        return;
    }

    if (caption.length > 100) {
        alert('Caption cannot exceed 100 characters.');
        e.preventDefault();
        return;
    }

    if (!caption.trim()) {
        alert('Caption cannot be empty.');
        e.preventDefault();
    }
});




