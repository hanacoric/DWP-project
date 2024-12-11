function showSection(sectionId) {
    document.getElementById('posts').style.display = sectionId === 'posts' ? 'block' : 'none';
    document.getElementById('trending').style.display = sectionId === 'trending' ? 'block' : 'none';

    document.querySelectorAll('.tabs a').forEach(tab => {
        tab.classList.remove('active');
    });

    const activeTab = document.querySelector(`.tabs a[onclick="showSection('${sectionId}')"]`);
    if (activeTab) {
        activeTab.classList.add('active');
    }
}

// Ensure the default section is "Posts"
document.addEventListener('DOMContentLoaded', () => {
    showSection('posts'); // Set default to "Posts"
});

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
