document.addEventListener('DOMContentLoaded', function() {
    const sharePostButton = document.querySelector('button[name="submit"]');
    const imageInput = document.querySelector('input[name="image"]');

    sharePostButton.addEventListener('click', function(event) {
        event.preventDefault();

        imageInput.click();
    });

    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.display = 'none';
            document.body.appendChild(img);


            const form = document.querySelector('form');
            form.submit();
        }

        reader.readAsDataURL(file);
    });

});