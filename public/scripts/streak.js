// Global streak popup handler
document.addEventListener('DOMContentLoaded', function() {
    // Check if streak info exists (set by PHP)
    if (typeof window.streakInfo === 'undefined' || !window.streakInfo) {
        return;
    }
    
    const streakInfo = window.streakInfo;
    const streakExtendedPopup = document.getElementById('streakExtendedPopup');
    const streakLostPopup = document.getElementById('streakLostPopup');
    const newStreakPopup = document.getElementById('newStreakPopup');
    
    if (streakInfo && streakInfo.status) {
        setTimeout(() => {
            switch(streakInfo.status) {
                case 'extended':
                    if (streakExtendedPopup) {
                        const extendedMessage = document.getElementById('streakExtendedMessage');
                        if (extendedMessage) {
                            extendedMessage.textContent = `Your streak is now ${streakInfo.streak} days!`;
                        }
                        streakExtendedPopup.classList.add('show');
                    }
                    break;
                case 'lost':
                    if (streakLostPopup) {
                        const lostMessage = document.getElementById('streakLostMessage');
                        if (lostMessage) {
                            lostMessage.textContent = `You lost your ${streakInfo.lostStreak} day streak...`;
                        }
                        streakLostPopup.classList.add('show');
                    }
                    break;
                case 'new':
                    if (newStreakPopup) {
                        newStreakPopup.classList.add('show');
                    }
                    break;
                // 'maintained' - no popup needed
            }
        }, 500);
    }
    
    // Close streak popups
    const streakExtendedClose = document.getElementById('streakExtendedClose');
    const streakLostClose = document.getElementById('streakLostClose');
    const newStreakClose = document.getElementById('newStreakClose');
    
    if (streakExtendedClose) {
        streakExtendedClose.addEventListener('click', () => {
            streakExtendedPopup.classList.remove('show');
        });
    }
    if (streakLostClose) {
        streakLostClose.addEventListener('click', () => {
            streakLostPopup.classList.remove('show');
        });
    }
    if (newStreakClose) {
        newStreakClose.addEventListener('click', () => {
            newStreakPopup.classList.remove('show');
        });
    }
});
