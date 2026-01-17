// Sample data for posts
const samplePosts = [
  {
    id: 1,
    title: "Silver MacBook Pro",
    type: "lost",
    category: "electronics",
    description: "Lost my Silver MacBook Pro 14-inch at the library on Jan 12. Had a blue sticker on the back. Please contact me if found.",
    location: "University Library, 3rd Floor",
    date: "2026-01-12",
    image: "https://via.placeholder.com/300x200?text=MacBook+Pro",
    user: "John Doe",
    email: "john.doe@university.edu"
  },
  {
    id: 2,
    title: "Blue Backpack with ID Card",
    type: "found",
    category: "accessories",
    description: "Found a blue backpack near the cafeteria. It contains an ID card. Owner can claim it by providing proof.",
    location: "University Cafeteria",
    date: "2026-01-13",
    image: "https://via.placeholder.com/300x200?text=Backpack",
    user: "Jane Smith",
    email: "jane.smith@university.edu"
  },
  {
    id: 3,
    title: "Student ID Card",
    type: "lost",
    category: "documents",
    description: "Lost my student ID card (Name: Ahmed Hassan, ID: STU-2024-001). Urgent! Please contact if found.",
    location: "Sports Complex",
    date: "2026-01-11",
    image: "https://via.placeholder.com/300x200?text=ID+Card",
    user: "Ahmed Hassan",
    email: "ahmed.hassan@university.edu"
  },
  {
    id: 4,
    title: "Gold Wedding Ring",
    type: "found",
    category: "accessories",
    description: "Found a beautiful gold wedding ring in the dormitory parking lot. Ring appears to be vintage.",
    location: "Dormitory Parking Lot A",
    date: "2026-01-13",
    image: "https://via.placeholder.com/300x200?text=Wedding+Ring",
    user: "Maria Garcia",
    email: "maria.garcia@university.edu"
  },
  {
    id: 5,
    title: "Red Winter Jacket",
    type: "lost",
    category: "clothing",
    description: "Missing red winter jacket from the gym. Has a small tear on the left sleeve. Really need it back!",
    location: "University Gym",
    date: "2026-01-10",
    image: "https://via.placeholder.com/300x200?text=Red+Jacket",
    user: "Sarah Williams",
    email: "sarah.williams@university.edu"
  },
  {
    id: 6,
    title: "Physics Textbook",
    type: "found",
    category: "documents",
    description: "Found a Physics textbook (Advanced Mechanics) in the science building. Has a name inside.",
    location: "Science Building, Room 201",
    date: "2026-01-12",
    image: "https://via.placeholder.com/300x200?text=Physics+Book",
    user: "Robert Chen",
    email: "robert.chen@university.edu"
  }
];

const sampleMessages = [
  {
    id: 1,
    sender: "John Doe",
    email: "john.doe@university.edu",
    subject: "Found your MacBook?",
    content: "Hi, I think I found your MacBook. It matches the description you posted. I'm in the engineering building.",
    date: "2026-01-14 10:30",
    read: false
  },
  {
    id: 2,
    sender: "Jane Smith",
    email: "jane.smith@university.edu",
    subject: "Is this your backpack?",
    content: "I found a blue backpack near the cafeteria. Can you confirm if it's yours?",
    date: "2026-01-13 15:45",
    read: false
  },
  {
    id: 3,
    sender: "Ahmed Hassan",
    email: "ahmed.hassan@university.edu",
    subject: "Thank you for finding my ID",
    content: "Thank you so much for finding my student ID card! I really appreciate your help.",
    date: "2026-01-11 09:20",
    read: true
  }
];

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function() {
  initializeNavigation();
  initializeDashboard();
  initializeSearch();
  initializeCreatePost();
  initializeInbox();
  initializeProfile();
  initializeSettings();
});

// ========== NAVIGATION ==========
function initializeNavigation() {
  const navLinks = document.querySelectorAll('.user-nav__link');
  
  navLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const sectionId = this.getAttribute('data-section');
      showSection(sectionId);
      
      // Update active state
      navLinks.forEach(l => l.classList.remove('active'));
      this.classList.add('active');
    });
  });
  
  // Set initial active section
  navLinks[0].classList.add('active');
}

function showSection(sectionId) {
  const sections = document.querySelectorAll('.section');
  sections.forEach(section => section.classList.remove('active'));
  
  const activeSection = document.getElementById(sectionId);
  if (activeSection) {
    activeSection.classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

// ========== PROFILE DROPDOWN ==========
function initializeProfile() {
  const profileBtn = document.getElementById('profileBtn');
  const profileDropdown = document.getElementById('profileDropdown');
  const dropdownLinks = profileDropdown.querySelectorAll('.dropdown-link');
  
  profileBtn.addEventListener('click', function() {
    profileDropdown.classList.toggle('active');
  });
  
  // Handle dropdown links for settings navigation
  dropdownLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      const sectionId = this.getAttribute('data-section');
      if (sectionId) {
        showSection(sectionId);
        
        // Update nav active state
        document.querySelectorAll('.user-nav__link').forEach(l => l.classList.remove('active'));
        document.querySelector(`[data-section="${sectionId}"]`).classList.add('active');
      }
      profileDropdown.classList.remove('active');
    });
  });
  
  // Close dropdown when clicking outside
  document.addEventListener('click', function(event) {
    if (!event.target.closest('.user-nav__profile')) {
      profileDropdown.classList.remove('active');
    }
  });
}

// ========== DASHBOARD SECTION ==========
function initializeDashboard() {
  populateRecentPosts();
}

function populateRecentPosts() {
  const grid = document.getElementById('recentPostsGrid');
  if (!grid) return;
  
  grid.innerHTML = '';
  samplePosts.slice(0, 3).forEach(post => {
    grid.appendChild(createPostCard(post));
  });
}

function createPostCard(post) {
  const card = document.createElement('div');
  card.className = 'post-card';
  
  const badgeClass = post.type === 'lost' ? 'post-badge--lost' : 'post-badge--found';
  const badgeText = post.type === 'lost' ? 'LOST' : 'FOUND';
  
  card.innerHTML = `
    <div style="position: relative;">
      <img src="${post.image}" alt="${post.title}" class="post-image">
      <span class="post-badge ${badgeClass}">${badgeText}</span>
    </div>
    <div class="post-content">
      <div class="post-header">
        <h3 class="post-title">${post.title}</h3>
        <span class="post-category">${capitalizeCategory(post.category)}</span>
      </div>
      <p class="post-description">${post.description}</p>
      <div class="post-meta">
        <div class="post-meta-item">
          <i class="fas fa-map-marker-alt"></i>
          <span>${post.location}</span>
        </div>
        <div class="post-meta-item">
          <i class="fas fa-calendar"></i>
          <span>${formatDate(post.date)}</span>
        </div>
      </div>
      <div class="post-actions">
        <button class="btn-contact" onclick="sendMessage('${post.email}', '${post.title}')">
          <i class="fas fa-envelope"></i> Contact
        </button>
      </div>
    </div>
  `;
  
  return card;
}

function capitalizeCategory(category) {
  return category.charAt(0).toUpperCase() + category.slice(1);
}

function formatDate(dateStr) {
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// ========== SEARCH SECTION ==========
function initializeSearch() {
  const searchBtn = document.getElementById('searchBtn');
  const searchInput = document.getElementById('searchInput');
  
  searchBtn.addEventListener('click', performSearch);
  searchInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') performSearch();
  });
}

function performSearch() {
  const searchTerm = document.getElementById('searchInput').value.toLowerCase();
  const categoryFilter = document.getElementById('categoryFilter').value;
  const typeFilter = document.getElementById('typeFilter').value;
  
  let results = samplePosts.filter(post => {
    const matchSearch = post.title.toLowerCase().includes(searchTerm) || 
                       post.description.toLowerCase().includes(searchTerm);
    const matchCategory = !categoryFilter || post.category === categoryFilter;
    const matchType = !typeFilter || post.type === typeFilter;
    
    return matchSearch && matchCategory && matchType;
  });
  
  displaySearchResults(results);
}

function displaySearchResults(results) {
  const grid = document.getElementById('searchResultsGrid');
  if (!grid) return;
  
  grid.innerHTML = '';
  
  if (results.length === 0) {
    grid.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
        <i class="fas fa-search" style="font-size: 48px; color: var(--primary-green); opacity: 0.3; margin-bottom: 15px; display: block;"></i>
        <p style="color: var(--text-muted);">No posts found. Try adjusting your search criteria.</p>
      </div>
    `;
  } else {
    results.forEach(post => {
      grid.appendChild(createPostCard(post));
    });
  }
}

// ========== CREATE POST SECTION ==========
function initializeCreatePost() {
  const form = document.getElementById('createPostForm');
  const fileInput = document.getElementById('itemImage');
  const fileLabel = document.querySelector('.file-label');
  
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      submitPost();
    });
  }
  
  // File upload preview
  if (fileInput) {
    fileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
          const preview = document.getElementById('imagePreview');
          preview.innerHTML = `<img src="${event.target.result}" alt="Preview">`;
        };
        reader.readAsDataURL(file);
      }
    });
    
    // Drag and drop
    const fileUpload = document.querySelector('.file-upload');
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      fileUpload.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
      e.preventDefault();
      e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
      fileUpload.addEventListener(eventName, function() {
        this.style.borderColor = 'var(--primary-blue)';
        this.style.background = 'var(--light-blue)';
      });
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
      fileUpload.addEventListener(eventName, function() {
        this.style.borderColor = 'var(--primary-green)';
        this.style.background = 'var(--light-green)';
      });
    });
    
    fileUpload.addEventListener('drop', function(e) {
      const dt = e.dataTransfer;
      const files = dt.files;
      fileInput.files = files;
      
      // Trigger change event
      const event = new Event('change', { bubbles: true });
      fileInput.dispatchEvent(event);
    });
  }
}

function submitPost() {
  const formData = new FormData(document.getElementById('createPostForm'));
  
  // Simulated submission
  showNotification('Post created successfully! It will be reviewed by moderators.', 'success');
  
  // Reset form
  document.getElementById('createPostForm').reset();
  document.getElementById('imagePreview').innerHTML = '';
  
  // Redirect to search after 2 seconds
  setTimeout(() => {
    showSection('search');
    document.querySelector('[data-section="search"]').classList.add('active');
  }, 2000);
}

// ========== INBOX SECTION ==========
function initializeInbox() {
  const inboxList = document.getElementById('inboxList');
  const composeBtn = document.getElementById('composeBtn');
  const composeModal = document.getElementById('composeModal');
  const closeModal = document.getElementById('closeModal');
  const cancelModal = document.getElementById('cancelModal');
  const composeForm = document.getElementById('composeForm');
  
  // Populate inbox
  if (inboxList) {
    inboxList.innerHTML = '';
    sampleMessages.forEach(msg => {
      const item = createMessageItem(msg);
      inboxList.appendChild(item);
    });
  }
  
  // Compose button
  if (composeBtn) {
    composeBtn.addEventListener('click', () => {
      composeModal.classList.add('active');
    });
  }
  
  // Close modal
  if (closeModal) {
    closeModal.addEventListener('click', () => {
      composeModal.classList.remove('active');
    });
  }
  
  if (cancelModal) {
    cancelModal.addEventListener('click', () => {
      composeModal.classList.remove('active');
    });
  }
  
  // Submit compose form
  if (composeForm) {
    composeForm.addEventListener('submit', function(e) {
      e.preventDefault();
      submitMessage();
    });
  }
  
  // Close modal when clicking outside
  window.addEventListener('click', function(event) {
    if (event.target === composeModal) {
      composeModal.classList.remove('active');
    }
  });
}

function createMessageItem(msg) {
  const item = document.createElement('div');
  item.className = 'message-item';
  if (msg.read) item.style.opacity = '0.7';
  
  item.innerHTML = `
    <div class="message-sender">${msg.sender}</div>
    <div class="message-subject">${msg.subject}</div>
    <div class="message-time">${msg.date}</div>
  `;
  
  item.addEventListener('click', () => {
    displayMessage(msg);
    
    // Update active state
    document.querySelectorAll('.message-item').forEach(m => m.classList.remove('active'));
    item.classList.add('active');
  });
  
  return item;
}

function displayMessage(msg) {
  const messageView = document.getElementById('messageView');
  
  messageView.innerHTML = `
    <div class="message-content">
      <div class="message-header">
        <div class="message-from">From: <strong>${msg.sender}</strong> &lt;${msg.email}&gt;</div>
        <div class="message-subject-full">${msg.subject}</div>
        <div class="message-date">${msg.date}</div>
      </div>
      <div class="message-body">${msg.content}</div>
      <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color);">
        <button onclick="sendReply('${msg.email}', 'Re: ${msg.subject}')" class="btn-submit" style="width: auto;">
          <i class="fas fa-reply"></i> Reply
        </button>
      </div>
    </div>
  `;
}

function sendMessage(email, subject) {
  document.getElementById('recipientEmail').value = email;
  document.getElementById('messageSubject').value = `Regarding: ${subject}`;
  document.getElementById('messageContent').value = '';
  document.getElementById('composeModal').classList.add('active');
}

function sendReply(email, subject) {
  document.getElementById('recipientEmail').value = email;
  document.getElementById('messageSubject').value = subject;
  document.getElementById('messageContent').value = '';
  document.getElementById('composeModal').classList.add('active');
}

function submitMessage() {
  const formData = {
    recipient: document.getElementById('recipientEmail').value,
    subject: document.getElementById('messageSubject').value,
    content: document.getElementById('messageContent').value
  };
  
  // Simulated submission
  showNotification('Message sent successfully!', 'success');
  document.getElementById('composeForm').reset();
  document.getElementById('composeModal').classList.remove('active');
}

// ========== SETTINGS SECTION ==========
function initializeSettings() {
  const settingsTabs = document.querySelectorAll('.settings-tab');
  const settingsContents = document.querySelectorAll('.settings-content');
  const profileForm = document.getElementById('profileForm');
  const accountForm = document.getElementById('accountForm');
  const preferencesForm = document.getElementById('preferencesForm');
  const profilePictureInput = document.getElementById('profilePicture');
  
  // Tab switching
  settingsTabs.forEach(tab => {
    tab.addEventListener('click', function() {
      const tabName = this.getAttribute('data-tab');
      
      // Update active tab
      settingsTabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      
      // Update active content
      settingsContents.forEach(content => content.classList.remove('active'));
      document.getElementById(tabName + '-tab').classList.add('active');
    });
  });
  
  // Profile picture upload preview
  if (profilePictureInput) {
    profilePictureInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
          document.getElementById('currentProfilePic').src = event.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }
  
  // Profile form submission
  if (profileForm) {
    profileForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      
      // Validate form
      if (!validateProfileForm()) {
        return;
      }
      
      // Simulated submission
      showNotification('Profile updated successfully!', 'success');
      
      // Update navbar or any other UI elements as needed
      setTimeout(() => {
        // You can add additional logic here if needed
      }, 1000);
    });
  }
  
  // Account form submission
  if (accountForm) {
    accountForm.addEventListener('submit', function(e) {
      e.preventDefault();
    });
    
    // Password change button
    const changePasswordBtn = accountForm.querySelector('button[type="button"]');
    if (changePasswordBtn) {
      changePasswordBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        // Validation
        if (!currentPassword || !newPassword || !confirmPassword) {
          showNotification('All password fields are required', 'error');
          return;
        }
        
        if (newPassword !== confirmPassword) {
          showNotification('New passwords do not match', 'error');
          return;
        }
        
        if (newPassword.length < 8) {
          showNotification('Password must be at least 8 characters long', 'error');
          return;
        }
        
        // Simulated submission
        showNotification('Password updated successfully!', 'success');
        
        // Clear password fields
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
      });
    }
    
    // Delete account button
    const deleteAccountBtn = accountForm.querySelector('.btn-danger');
    if (deleteAccountBtn) {
      deleteAccountBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        const confirmDelete = confirm(
          'Are you sure you want to delete your account? This action cannot be undone.\n\n' +
          'All your posts, messages, and profile information will be permanently deleted.'
        );
        
        if (confirmDelete) {
          const finalConfirm = prompt('Type "DELETE" to confirm account deletion:');
          if (finalConfirm === 'DELETE') {
            // Simulated deletion
            showNotification('Account deleted successfully', 'success');
            setTimeout(() => {
              window.location.href = 'index.html';
            }, 2000);
          } else {
            showNotification('Account deletion cancelled', 'info');
          }
        }
      });
    }
    
    // 2FA Toggle
    const twoFactorToggle = document.getElementById('twoFactorAuth');
    if (twoFactorToggle) {
      twoFactorToggle.addEventListener('change', function() {
        if (this.checked) {
          showNotification('2FA has been enabled. Check your email for setup instructions.', 'success');
        } else {
          showNotification('2FA has been disabled.', 'success');
        }
      });
    }
  }
  
  // Preferences form submission
  if (preferencesForm) {
    preferencesForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Simulated submission
      showNotification('Preferences saved successfully!', 'success');
    });
  }
}

function validateProfileForm() {
  const name = document.getElementById('profileName').value.trim();
  const email = document.getElementById('profileEmail').value.trim();
  const university = document.getElementById('profileUniversity').value.trim();
  
  if (!name) {
    showNotification('Full name is required', 'error');
    return false;
  }
  
  if (!email || !email.includes('@')) {
    showNotification('Valid email is required', 'error');
    return false;
  }
  
  if (!university) {
    showNotification('University is required', 'error');
    return false;
  }
  
  return true;
}

// ========== NOTIFICATION ENHANCEMENT ==========
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  const bgColor = type === 'success' ? 'var(--primary-green)' : 
                  type === 'error' ? '#ff6b6b' : 'var(--primary-blue)';
  
  notification.style.cssText = `
    position: fixed;
    top: 100px;
    right: 20px;
    padding: 16px 24px;
    background: ${bgColor};
    color: white;
    border-radius: 8px;
    box-shadow: var(--shadow);
    z-index: 3000;
    animation: slideIn 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
  `;
  
  const icon = type === 'success' ? '✓' : 
               type === 'error' ? '✕' : 'ℹ';
  
  notification.innerHTML = `<span style="font-weight: bold;">${icon}</span>${message}`;
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// ========== UTILITIES ==========
// Update existing showNotification (if it's called separately, use the enhanced version above)


// Add animations
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
