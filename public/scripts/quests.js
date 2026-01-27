document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            this.classList.add('active');
            document.querySelector('.tab-content.' + targetTab).classList.add('active');
        });
    });

    // Quest claim handling
    const claimButtons = document.querySelectorAll('.claim-btn');
    const popup = document.getElementById('purchasePopup');
    const popupTitle = document.getElementById('popupTitle');
    const popupMessage = document.getElementById('popupMessage');
    const popupIcon = document.querySelector('.popup-icon');
    const popupClose = document.getElementById('popupClose');
    
    claimButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questId = this.getAttribute('data-quest-id');
            if (!questId) return;
            
            const btn = this;
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Claiming...';
            
            fetch('/quests/claim', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ quest_id: parseInt(questId) })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    popupIcon.textContent = '🎉';
                    popupTitle.textContent = 'Quest Completed!';
                    popupMessage.textContent = data.message;
                    popup.classList.add('show');
                    
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    popupIcon.textContent = '❌';
                    popupTitle.textContent = 'Error';
                    popupMessage.textContent = data.message;
                    popup.classList.add('show');
                    
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                popupIcon.textContent = '❌';
                popupTitle.textContent = 'Error';
                popupMessage.textContent = 'Something went wrong. Please try again.';
                popup.classList.add('show');
                
                btn.disabled = false;
                btn.textContent = originalText;
            });
        });
    });

    // Purchase handling
    const buyButtons = document.querySelectorAll('.buy-btn:not([disabled])');

    // Close popup when clicking OK button
    popupClose.addEventListener('click', function() {
        popup.classList.remove('show');
    });

    // Close popup when clicking outside
    popup.addEventListener('click', function(e) {
        if (e.target === popup) {
            popup.classList.remove('show');
        }
    });

    buyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            if (!itemId) return;

            const btn = this;
            // Disable button during purchase
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Processing...';

            // Send purchase request
            fetch('/quests/purchase', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ item_id: parseInt(itemId) })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success popup
                    popupIcon.textContent = '🎉';
                    popupTitle.textContent = 'Purchase Successful!';
                    popupMessage.textContent = data.message;
                    popup.classList.add('show');

                    // Reload page after short delay to update stats and owned items
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error popup
                    popupIcon.textContent = '❌';
                    popupTitle.textContent = 'Purchase Failed';
                    popupMessage.textContent = data.message;
                    popup.classList.add('show');

                    // Re-enable button
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                popupIcon.textContent = '❌';
                popupTitle.textContent = 'Error';
                popupMessage.textContent = 'Something went wrong. Please try again.';
                popup.classList.add('show');

                btn.disabled = false;
                btn.textContent = originalText;
            });
        });
    });
});
