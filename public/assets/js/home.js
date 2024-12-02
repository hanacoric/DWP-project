function showSection(sectionId) {
    document.getElementById('posts').style.display = 'none';
    document.getElementById('trending').style.display = 'none';

    document.querySelectorAll('.tabs a').forEach(tab => tab.classList.remove('active'));

    document.getElementById(sectionId).style.display = 'block';
    document.querySelector('.tabs a[onclick="showSection(\'' + sectionId + '\')"]').classList.add('active');
}

document.querySelectorAll('.like-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const postId = this.dataset.postId;
        const action = this.querySelector('button[name="action"]:focus').value;

        // Send like/unlike data via form submission
        fetch('index.php', {
            method: 'POST',
            body: new FormData(this)
        })
            .then(response => response.text())
            .then(html => {
                // Replace the post content with the updated content
                document.getElementById(`post-${postId}`).innerHTML = html;
            });
    });
});
