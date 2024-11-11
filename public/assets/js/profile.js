document.getElementById('profile_picture').addEventListener('change', function(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const profileImage = document.querySelector('.profile-picture');
        profileImage.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
});
