function showSection(sectionId) {
    document.getElementById('posts').style.display = 'none';
    document.getElementById('trending').style.display = 'none';

    document.querySelectorAll('.tabs a').forEach(tab => tab.classList.remove('active'));

    document.getElementById(sectionId).style.display = 'block';
    document.querySelector('.tabs a[onclick="showSection(\'' + sectionId + '\')"]').classList.add('active');
}