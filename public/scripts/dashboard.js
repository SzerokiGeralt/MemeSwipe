document.addEventListener('DOMContentLoaded', function() {
    const postContainer = document.getElementById('post-container');
    const postImage = document.getElementById('post-image');
    const upvoteBtn = document.getElementById('upvote-btn');
    const downvoteBtn = document.getElementById('downvote-btn');
    const skipBtn = document.getElementById('skip-btn');
    const rewardPopup = document.getElementById('rewardPopup');
    const levelUpPopup = document.getElementById('levelUpPopup');
    const levelUpClose = document.getElementById('levelUpClose');
    
    // Streak popups
    const streakExtendedPopup = document.getElementById('streakExtendedPopup');
    const streakLostPopup = document.getElementById('streakLostPopup');
    const newStreakPopup = document.getElementById('newStreakPopup');

    let isVoting = false;
    
    // Close streak popups
    document.getElementById('streakExtendedClose')?.addEventListener('click', () => {
        streakExtendedPopup.classList.remove('show');
    });
    document.getElementById('streakLostClose')?.addEventListener('click', () => {
        streakLostPopup.classList.remove('show');
    });
    document.getElementById('newStreakClose')?.addEventListener('click', () => {
        newStreakPopup.classList.remove('show');
    });

    // Vote function
    async function vote(voteType) {
        if (isVoting) return;
        
        const postId = postContainer.dataset.postId;
        if (!postId) return;

        isVoting = true;
        
        // Disable buttons
        if (upvoteBtn) upvoteBtn.disabled = true;
        if (downvoteBtn) downvoteBtn.disabled = true;
        if (skipBtn) skipBtn.disabled = true;

        try {
            const response = await fetch('/dashboard/vote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    post_id: parseInt(postId),
                    vote_type: voteType
                })
            });

            const data = await response.json();

            if (data.success) {
                // Show reward popup
                showRewardPopup(data.rewards.exp, data.rewards.diamonds, voteType);
                
                // Update stats in navbar
                updateNavbarStats(data.stats);
                
                // Check for level up
                if (data.levelUp && data.levelUp.leveled) {
                    setTimeout(() => {
                        showLevelUpPopup(data.levelUp.newLevel, data.levelUp.bonusDiamonds);
                    }, 1500);
                }
                
                // Load next post after animation
                setTimeout(() => {
                    loadNextPost(data.nextPost);
                }, 1000);
            } else {
                console.error('Vote failed:', data.message);
                // Re-enable buttons on error
                enableButtons();
            }
        } catch (error) {
            console.error('Error voting:', error);
            enableButtons();
        }

        isVoting = false;
    }

    // Show reward popup
    function showRewardPopup(exp, diamonds, voteType) {
        const rewardExp = document.getElementById('reward-exp');
        const rewardDiamonds = document.getElementById('reward-diamonds');
        
        rewardExp.textContent = `+${exp} XP`;
        rewardDiamonds.textContent = `+${diamonds} 💎`;
        
        // Position popup near the clicked button
        rewardPopup.classList.add('show');
        rewardPopup.classList.add(voteType);
        
        // Animate the post image
        postImage.classList.add(voteType === 'upvote' ? 'swipe-right' : 'swipe-left');
        
        setTimeout(() => {
            rewardPopup.classList.remove('show');
            rewardPopup.classList.remove(voteType);
        }, 1500);
    }

    // Show level up popup
    function showLevelUpPopup(newLevel, bonusDiamonds) {
        document.getElementById('levelUpMessage').textContent = `You reached level ${newLevel}!`;
        document.getElementById('levelUpBonus').textContent = `Bonus: +${bonusDiamonds} 💎`;
        levelUpPopup.classList.add('show');
    }

    // Update navbar stats
    function updateNavbarStats(stats) {
        const levelEl = document.getElementById('user-level');
        const diamondsEl = document.getElementById('user-diamonds');
        const meterEl = document.getElementById('exp-meter');
        
        if (levelEl) levelEl.textContent = stats.level;
        if (diamondsEl) diamondsEl.textContent = stats.diamonds.toLocaleString();
        if (meterEl) meterEl.value = stats.expPercentage;
    }

    // Load next post
    function loadNextPost(nextPost) {
        if (nextPost) {
            postContainer.dataset.postId = nextPost.id;
            postImage.src = nextPost.image;
            postImage.classList.remove('swipe-right', 'swipe-left');
            postImage.classList.add('slide-in');
            
            // Update post author if element exists
            const postAuthor = document.querySelector('.post-author');
            if (postAuthor) {
                postAuthor.textContent = '@' + nextPost.username;
                postAuthor.onclick = function() {
                    window.location.href = '/profile/' + nextPost.username;
                };
            }
            
            setTimeout(() => {
                postImage.classList.remove('slide-in');
                enableButtons();
            }, 300);
        } else {
            // No more posts
            postContainer.innerHTML = `
                <div class="no-posts">
                    <p>🎉 You've voted on all posts!</p>
                    <p>Check back later for new content!</p>
                    <a href="/upload" class="upload-link">Upload a Meme</a>
                </div>
            `;
            if (upvoteBtn) upvoteBtn.disabled = true;
            if (downvoteBtn) downvoteBtn.disabled = true;
            if (skipBtn) skipBtn.disabled = true;
        }
    }

    // Enable buttons
    function enableButtons() {
        if (upvoteBtn) upvoteBtn.disabled = false;
        if (downvoteBtn) downvoteBtn.disabled = false;
        if (skipBtn) skipBtn.disabled = false;
    }

    // Skip to next post without voting
    async function skipPost() {
        if (isVoting) return;
        isVoting = true;
        
        postImage.classList.add('fade-out');
        
        try {
            const response = await fetch('/dashboard/next');
            const data = await response.json();
            
            setTimeout(() => {
                postImage.classList.remove('fade-out');
                if (data.post) {
                    loadNextPost(data.post);
                }
            }, 300);
        } catch (error) {
            console.error('Error skipping:', error);
            enableButtons();
        }
        
        isVoting = false;
    }

    // Event listeners
    if (upvoteBtn) {
        upvoteBtn.addEventListener('click', () => vote('upvote'));
    }
    if (downvoteBtn) {
        downvoteBtn.addEventListener('click', () => vote('downvote'));
    }
    if (skipBtn) {
        skipBtn.addEventListener('click', skipPost);
    }
    
    // Close level up popup
    levelUpClose.addEventListener('click', () => {
        levelUpPopup.classList.remove('show');
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft' || e.key === 'a') {
            if (downvoteBtn && !downvoteBtn.disabled) vote('downvote');
        } else if (e.key === 'ArrowRight' || e.key === 'd') {
            if (upvoteBtn && !upvoteBtn.disabled) vote('upvote');
        } else if (e.key === 'ArrowDown' || e.key === 's') {
            if (skipBtn && !skipBtn.disabled) skipPost();
        }
    });

    // Touch swipe support
    let touchStartX = 0;
    let touchEndX = 0;

    if (postContainer) {
        postContainer.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });

        postContainer.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
    }

    function handleSwipe() {
        const diff = touchEndX - touchStartX;
        if (Math.abs(diff) > 50) {
            if (diff > 0) {
                // Swipe right = upvote
                vote('upvote');
            } else {
                // Swipe left = downvote
                vote('downvote');
            }
        }
    }
});
