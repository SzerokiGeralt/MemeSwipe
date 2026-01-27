// Upload page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Cooldown timer functionality
    const timerElement = document.getElementById('cooldownTimer');
    if (timerElement && window.cooldownSeconds !== undefined) {
        let totalSeconds = window.cooldownSeconds;
        
        function updateTimer() {
            if (totalSeconds <= 0) {
                location.reload();
                return;
            }
            
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            
            timerElement.textContent = 
                String(hours).padStart(2, '0') + ':' + 
                String(minutes).padStart(2, '0') + ':' + 
                String(seconds).padStart(2, '0');
            
            totalSeconds--;
        }
        
        setInterval(updateTimer, 1000);
    }
    
    // Upload form handling
    const uploadForm = document.getElementById('uploadForm');
    if (!uploadForm) return; // Exit if upload form not available (cooldown active)
    
    const imageInput = document.getElementById('imageInput');
    const selectFileBtn = document.getElementById('selectFileBtn');
    const submitBtn = document.getElementById('submitBtn');
    const dropZone = document.getElementById('dropZone');
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const removePreview = document.getElementById('removePreview');
    const popup = document.getElementById('uploadPopup');
    const popupIcon = document.getElementById('popupIcon');
    const popupTitle = document.getElementById('popupTitle');
    const popupMessage = document.getElementById('popupMessage');
    const popupClose = document.getElementById('popupClose');
    
    selectFileBtn.addEventListener('click', () => imageInput.click());
    
    // Drag and drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drag-over');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            imageInput.files = files;
            handleFileSelect(files[0]);
        }
    });
    
    imageInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });
    
    function handleFileSelect(file) {
        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            showPopup('❌', 'Invalid File', 'Please select a JPEG, PNG, GIF, or WebP image.');
            return;
        }
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showPopup('❌', 'File Too Large', 'Maximum file size is 5MB.');
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.src = e.target.result;
            previewContainer.style.display = 'block';
            selectFileBtn.style.display = 'none';
            submitBtn.disabled = false;
        };
        reader.readAsDataURL(file);
    }
    
    removePreview.addEventListener('click', () => {
        imageInput.value = '';
        previewContainer.style.display = 'none';
        selectFileBtn.style.display = 'inline-block';
        submitBtn.disabled = true;
    });
    
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!imageInput.files.length) {
            showPopup('❌', 'No File Selected', 'Please select an image to upload.');
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading...';
        
        const formData = new FormData();
        formData.append('image', imageInput.files[0]);
        
        try {
            const response = await fetch('/upload/submit', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                let message = data.message;
                if (data.quests_completed) {
                    message += ` Quest bonus: +${data.quest_reward} 💎`;
                }
                showPopup('🎉', 'Upload Successful!', message);
                
                setTimeout(() => location.reload(), 2500);
            } else {
                showPopup('❌', 'Upload Failed', data.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Upload Meme';
            }
        } catch (error) {
            console.error('Error:', error);
            showPopup('❌', 'Error', 'Something went wrong. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Upload Meme';
        }
    });
    
    function showPopup(icon, title, message) {
        popupIcon.textContent = icon;
        popupTitle.textContent = title;
        popupMessage.textContent = message;
        popup.classList.add('show');
    }
    
    popupClose.addEventListener('click', () => popup.classList.remove('show'));
    popup.addEventListener('click', (e) => {
        if (e.target === popup) popup.classList.remove('show');
    });
});
