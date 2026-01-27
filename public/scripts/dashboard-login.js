document.addEventListener('DOMContentLoaded', function() {
    const loginPromptBtn = document.getElementById('login-prompt-btn');
    const loginPopup = document.getElementById('loginPopup');
    
    if (loginPromptBtn) {
        loginPromptBtn.addEventListener('click', () => {
            loginPopup.classList.add('show');
        });
    }
    
    loginPopup.addEventListener('click', (e) => {
        if (e.target === loginPopup) {
            loginPopup.classList.remove('show');
        }
    });
});
