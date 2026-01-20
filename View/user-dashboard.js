
let postsData = [];
let messagesData = [];


const DEFAULT_POST_IMAGE = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='200' viewBox='0 0 300 200'%3E%3Crect fill='%23f0f0f0' width='300' height='200'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%23999' font-family='sans-serif' font-size='16'%3ENo Image%3C/text%3E%3C/svg%3E";


const API_BASE = '../Controller';


async function fetchAPI(endpoint, options = {}) {
  try {
    const response = await fetch(`${API_BASE}/${endpoint}`, {
      credentials: 'include',
      ...options
    });
    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'API request failed');
    }
    return data;
  } catch (error) {
    console.error('API Error:', error);
    return { success: false, message: error.message, data: [] };
  }
}





document.addEventListener('DOMContentLoaded', function() {

  checkUserStatus().then(isValid => {
    if (isValid) {
      initializeNavigation();
      loadInitialData();
      initializeSearch();
      initializeCreatePost();
      initializeInbox();
      initializeProfile();
      initializeSettings();
      loadUserProfile();
    }
  
    function handleLogoutUser() {
      fetch('../Controller/auth_api.php?action=logout', {
        method: 'POST',
        credentials: 'include'
      })
      .then(res => res.json())
      .then(() => window.location.href = './index.html')
      .catch(() => window.location.href = './index.html');
    }
  
    const userLogoutBtn = document.getElementById('logoutBtn');
    if (userLogoutBtn) userLogoutBtn.addEventListener('click', function(e){ e.preventDefault(); handleLogoutUser(); });
  });
});


async function checkUserStatus() {
  try {
    const response = await fetch(`${API_BASE}/user_api.php?action=check_status`, {
      credentials: 'include'
    });
    const data = await response.json();
    
    if (!response.ok || !data.success) {

      alert(data.message || 'Your session has expired or your account is not active. Please login again.');
      window.location.href = '../Controller/login.php';
      return false;
    }
    
    return true;
  } catch (error) {
    console.error('Status check error:', error);
    alert('Unable to verify your session. Please login again.');
    window.location.href = '../Controller/login.php';
    return false;
  }
}


async function loadInitialData() {
  await loadPosts();
  await loadMessages();
  await loadMyPosts();
  initializeDashboard();
  populateInboxList();
}

async function loadPosts() {
  const result = await fetchAPI('post_api.php?action=get_posts');
  if (result.success && result.data) {
    postsData = result.data.map(post => {
      let postDate = 'Unknown';
      if (post.date_time) {
        postDate = post.date_time.split(' ')[0];
      } else if (post.created_at) {
        postDate = post.created_at.split(' ')[0];
      }
      
      return {
        id: post.post_id,
        title: post.item_name || 'Untitled',
        type: post.post_type || 'lost',
        category: post.category || 'other',
        description: post.item_description || 'No description provided',
        location: post.location || 'Unknown location',
        date: postDate,
        image: post.image_url || DEFAULT_POST_IMAGE,
        user: post.user_name || 'Anonymous',
        email: post.email || '',
        flagged: post.flagged == 1
      };
    });
  } else {

    postsData = [];
  }
}


async function loadMessages() {
  const result = await fetchAPI('user_api.php?action=get_messages');
  if (result.success && result.data) {
    messagesData = result.data.map(msg => ({
      id: msg.message_id,
      sender: msg.sender_name,
      email: msg.sender_email,
      subject: msg.subject || 'No Subject',
      content: msg.message_content,
      date: msg.sent_at,
      read: msg.is_read == 1
    }));
  } else {
    messagesData = [];
  }

  updateInboxBadge();
}


function updateInboxBadge() {
  const badge = document.getElementById('inboxBadge');
  if (!badge) return;
  
  const unreadCount = messagesData.filter(msg => !msg.read).length;
  
  if (unreadCount > 0) {
    badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
    badge.style.display = 'inline-flex';
  } else {
    badge.style.display = 'none';
  }
}


let myPostsData = [];

async function loadMyPosts() {
  const result = await fetchAPI('post_api.php?action=get_my_posts');
  if (result.success && result.data) {
    myPostsData = result.data.map(post => {
      let postDate = 'Unknown';
      let dateTime = '';
      if (post.date_time) {
        postDate = post.date_time.split(' ')[0];
        dateTime = post.date_time;
      } else if (post.created_at) {
        postDate = post.created_at.split(' ')[0];
        dateTime = post.created_at;
      }

      return {
        id: post.post_id,
        title: post.item_name || 'Untitled',
        type: post.post_type || 'lost',
        category: post.category || 'other',
        description: post.item_description || 'No description provided',
        location: post.location || 'Unknown location',
        date: postDate,
        date_time: dateTime,
        image: post.image_url || DEFAULT_POST_IMAGE,
        status: post.post_status || 'pending',
        flagged: post.flagged == 1
      };
    });
  } else {
    myPostsData = [];
  }
  displayMyPosts();
}

function displayMyPosts() {
  const grid = document.getElementById('myPostsGrid');
  if (!grid) return;
  
  if (myPostsData.length === 0) {
    grid.innerHTML = `
      <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #999;">
        <i class="fas fa-clipboard-list" style="font-size: 48px; margin-bottom: 16px;"></i>
        <p>You haven't created any posts yet.</p>
        <button class="btn-primary" onclick="showSection('create')" style="margin-top: 16px;">Create Your First Post</button>
      </div>
    `;
    return;
  }
  
  grid.innerHTML = myPostsData.map(post => createMyPostCard(post)).join('');
}

function createMyPostCard(post) {
  const badgeClass = post.type === 'lost' ? 'post-badge--lost' : 'post-badge--found';
  const badgeText = post.type === 'lost' ? 'LOST' : 'FOUND';
  const statusClass = post.status === 'approved' ? 'status-approved' : (post.status === 'pending' ? 'status-pending' : 'status-banned');
  const statusText = post.status.charAt(0).toUpperCase() + post.status.slice(1);
  const flagIcon = post.flagged ? '<span class="post-flag-icon" title="This post has been flagged by a moderator" style="position: absolute; top: 8px; left: 8px; background: #ef4444; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;"><i class="fas fa-flag"></i> Flagged</span>' : '';
  
  return `
    <div class="post-card my-post-card" data-post-id="${post.id}">
      <div style="position: relative;">
        <img src="${post.image}" alt="${post.title}" class="post-image">
        <span class="post-badge ${badgeClass}">${badgeText}</span>
        ${flagIcon}
        <span class="post-status-badge ${statusClass}" style="position: absolute; top: 8px; right: 8px; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; color: white; background: ${post.status === 'approved' ? '#149444' : (post.status === 'pending' ? '#f59e0b' : '#ef4444')};">
          ${statusText}
        </span>
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
        <div class="post-actions" style="margin-top: 12px; display: flex; gap: 8px;">
          <button class="btn-edit" onclick="editPost(${post.id})" style="flex: 1; padding: 8px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
            <i class="fas fa-edit"></i> Edit
          </button>
          <button class="btn-delete" onclick="deletePost(${post.id})" style="flex: 1; padding: 8px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">
            <i class="fas fa-trash"></i> Delete
          </button>
        </div>
      </div>
    </div>
  `;
}

async function deletePost(postId) {
  if (!confirm('Are you sure you want to delete this post?')) return;
  
  const formData = new FormData();
  formData.append('post_id', postId);
  
  const result = await fetchAPI('post_api.php?action=delete_post', {
    method: 'POST',
    body: formData
  });
  
  if (result.success) {
    alert('Post deleted successfully!');
    await loadMyPosts();
    await loadPosts(); // Refresh main posts too
  } else {
    alert(result.message || 'Failed to delete post');
  }
}

function editPost(postId) {
  let post = myPostsData.find(p => String(p.id) === String(postId));
  if (!post) {
    showNotification('Edit error: Post not found.', 'error');
    console.error('Edit error: Post not found for id', postId, myPostsData);
    return;
  }

  const modal = document.getElementById('editPostModal');
  if (modal) modal.style.display = 'flex';

  setTimeout(() => {
    const form = document.getElementById('editPostForm');
    if (!form) return;
    form.reset();
    const postTypeInput = form.querySelector(`input[name="post_type"][value="${post.type}"]`);
    if (postTypeInput) postTypeInput.checked = true;
    form.dataset.editingPostId = postId;
  }, 100);
}
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('editPostModal');
  const closeBtn = document.getElementById('closeEditModal');
  if (closeBtn && modal) {
    closeBtn.onclick = () => { modal.style.display = 'none'; };
  }
  window.onclick = function(event) {
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  };

  const editForm = document.getElementById('editPostForm');
  if (editForm) {
    editForm.onsubmit = async function(e) {
      e.preventDefault();
      const formData = new FormData(editForm);
      formData.append('action', 'update_post');
      formData.append('post_id', editForm.dataset.editingPostId);
      try {
        const response = await fetch(`${API_BASE}/post_api.php`, {
          method: 'POST',
          body: formData,
          credentials: 'include'
        });
        const result = await response.json();
        if (result.success) {
          showNotification('Post updated successfully!', 'success');
          modal.style.display = 'none';
          await loadMyPosts();
        } else {
          showNotification(result.message || 'Failed to update post', 'error');
        }
      } catch (error) {
        showNotification('Error updating post. Please try again.', 'error');
      }
    };
  }
});


function initializeNavigation() {
  const navLinks = document.querySelectorAll('.user-nav__link');
  const dropdownLinks = document.querySelectorAll('.dropdown-link');

  const handleLinkClick = function(e) {
    e.preventDefault();
    const sectionId = this.getAttribute('data-section');
    showSection(sectionId);

    navLinks.forEach(l => l.classList.remove('active'));
    this.classList.add('active');
  };

  navLinks.forEach(link => {
    link.addEventListener('click', handleLinkClick);
  });

  dropdownLinks.forEach(link => {
    link.addEventListener('click', handleLinkClick);
  });

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


function initializeProfile() {
  const profileBtn = document.getElementById('profileBtn');
  const profileDropdown = document.getElementById('profileDropdown');
  const dropdownLinks = profileDropdown.querySelectorAll('.dropdown-link');
  
  profileBtn.addEventListener('click', function() {
    profileDropdown.classList.toggle('active');
  });
  

  dropdownLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      const sectionId = this.getAttribute('data-section');
      if (sectionId) {
        showSection(sectionId);
        

        document.querySelectorAll('.user-nav__link').forEach(l => l.classList.remove('active'));
        document.querySelector(`[data-section="${sectionId}"]`).classList.add('active');
      }
      profileDropdown.classList.remove('active');
    });
  });
  

  document.addEventListener('click', function(event) {
    if (!event.target.closest('.user-nav__profile')) {
      profileDropdown.classList.remove('active');
    }
  });
}


function initializeDashboard() {
  populateRecentPosts();
  initializeStatCards();
}


function initializeStatCards() {
  const statCards = document.querySelectorAll('.stat-card[data-navigate]');
  
  statCards.forEach(card => {
    card.addEventListener('click', function() {
      const sectionId = this.getAttribute('data-navigate');
      if (sectionId) {
        showSection(sectionId);
        

        const navLinks = document.querySelectorAll('.user-nav__link');
        navLinks.forEach(link => link.classList.remove('active'));
        const targetNav = document.querySelector(`.user-nav__link[data-section="${sectionId}"]`);
        if (targetNav) targetNav.classList.add('active');
      }
    });
  });
}

function populateRecentPosts() {
  const grid = document.getElementById('recentPostsGrid');
  if (!grid) return;
  
  grid.innerHTML = '';
  
  if (postsData.length === 0) {
    grid.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">No posts available yet.</p>';
    return;
  }
  
  postsData.slice(0, 3).forEach(post => {
    grid.appendChild(createPostCard(post));
  });
}

function createPostCard(post) {
  const card = document.createElement('div');
  card.className = 'post-card';
  card.style.cursor = 'pointer';
  
  const badgeClass = post.type === 'lost' ? 'post-badge--lost' : 'post-badge--found';
  const badgeText = post.type === 'lost' ? 'LOST' : 'FOUND';
  const flagIcon = post.flagged ? '<span class="post-flag-icon" title="This post has been flagged for review" style="position: absolute; top: 8px; right: 8px; background: #ef4444; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;"><i class="fas fa-flag"></i></span>' : '';
  
  card.innerHTML = `
    <div style="position: relative;">
      <img src="${post.image}" alt="${post.title}" style="width: 100%; max-height: 350px; object-fit: cover;">
      <span class="post-badge ${badgeClass}" style="position: absolute; top: 15px; left: 15px;">${badgeText}</span>
      ${flagIcon}
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
        <button class="btn-contact" onclick="event.stopPropagation(); sendMessage('${post.email}', '${post.title}')">
          <i class="fas fa-envelope"></i> Contact
        </button>
      </div>
    </div>
  `;
  

  card.addEventListener('click', () => openPostDetail(post));
  
  return card;
}


function openPostDetail(post) {
  const badgeClass = post.type === 'lost' ? 'post-badge--lost' : 'post-badge--found';
  const badgeText = post.type === 'lost' ? 'LOST' : 'FOUND';
  
  let modal = document.getElementById('postDetailModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'postDetailModal';
    modal.className = 'modal';
    document.body.appendChild(modal);
  }
  
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 700px;">
      <div class="modal-header">
        <h2>Post Details</h2>
        <button class="modal-close" onclick="closePostDetail()">&times;</button>
      </div>
      <div class="modal-body" style="padding: 0;">
        <div style="position: relative;">
          <img src="${post.image}" alt="${post.title}" style="width: 100%; max-height: 350px; object-fit: cover;">
          <span class="post-badge ${badgeClass}" style="position: absolute; top: 15px; left: 15px;">${badgeText}</span>
        </div>
        <div style="padding: 25px;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
            <h2 style="margin: 0; color: var(--text-dark);">${post.title}</h2>
            <span class="post-category" style="flex-shrink: 0;">${capitalizeCategory(post.category)}</span>
          </div>
          
          <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 20px;">${post.description}</p>
          
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px; color: var(--text-muted);">
              <i class="fas fa-map-marker-alt" style="color: var(--primary-green);"></i>
              <span><strong>Location:</strong> ${post.location}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; color: var(--text-muted);">
              <i class="fas fa-calendar" style="color: var(--primary-green);"></i>
              <span><strong>Date:</strong> ${formatDate(post.date)}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; color: var(--text-muted);">
              <i class="fas fa-user" style="color: var(--primary-green);"></i>
              <span><strong>Posted by:</strong> ${post.user}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; color: var(--text-muted);">
              <i class="fas fa-envelope" style="color: var(--primary-green);"></i>
              <span><strong>Email:</strong> ${post.email}</span>
            </div>
          </div>
          
          <div style="display: flex; gap: 10px; padding-top: 15px; border-top: 1px solid var(--border-color);">
            <button class="btn-submit" onclick="sendMessage('${post.email}', '${post.title}'); closePostDetail();">
              <i class="fas fa-envelope"></i> Contact Owner
            </button>
            <button class="btn-cancel" onclick="closePostDetail()">Close</button>
          </div>
        </div>
      </div>
    </div>
  `;
  
  modal.classList.add('active');
  

  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closePostDetail();
    }
  });
}

function closePostDetail() {
  const modal = document.getElementById('postDetailModal');
  if (modal) {
    modal.classList.remove('active');
  }
}

function capitalizeCategory(category) {
  return category.charAt(0).toUpperCase() + category.slice(1);
}

function formatDate(dateStr) {
  if (!dateStr || dateStr === 'Unknown') return 'Unknown date';
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr; // Return original if invalid
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}


function initializeSearch() {
  const searchBtn = document.getElementById('searchBtn');
  const searchInput = document.getElementById('searchInput');
  
  searchBtn.addEventListener('click', performSearch);
  searchInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') performSearch();
  });
}

function performSearch() {
  const searchInputEl = document.getElementById('searchInput');
  const categoryFilterEl = document.getElementById('categoryFilter');
  const typeFilterEl = document.getElementById('typeFilter');
  
  const searchTerm = searchInputEl ? searchInputEl.value.toLowerCase() : '';
  const categoryFilter = categoryFilterEl ? categoryFilterEl.value : '';
  const typeFilter = typeFilterEl ? typeFilterEl.value : '';
  
  let results = postsData.filter(post => {
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
      
      const event = new Event('change', { bubbles: true });
      fileInput.dispatchEvent(event);
    });
  }
}

async function submitPost() {
  const form = document.getElementById('createPostForm');
  const preview = document.getElementById('imagePreview');
  const searchNav = document.querySelector('[data-section="search"]');

  if (form) {
    const formData = new FormData(form);
    const isEditing = form.dataset.editingPostId;
    const action = isEditing ? 'update_post' : 'create_post';
    formData.append('action', action);
    if (isEditing) {
      formData.append('post_id', isEditing);
    }

    try {
      const response = await fetch(`${API_BASE}/post_api.php`, {
        method: 'POST',
        body: formData,
        credentials: 'include'
      });
      const result = await response.json();

      if (result.success) {
        const message = isEditing ? 'Post updated successfully!' : 'Post created successfully! It will be reviewed by moderators.';
        showNotification(message, 'success');
        form.reset();
        if (preview) preview.innerHTML = '';
        delete form.dataset.editingPostId;

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.innerHTML = '<i class="fas fa-plus"></i> Create Post';
        }

        await loadPosts();
        await loadMyPosts();
        if (!isEditing) {
          setTimeout(() => {
            showSection('search');
            if (searchNav) searchNav.classList.add('active');
          }, 2000);
        } else {
          showSection('myposts');
        }
      } else {
        showNotification(result.message || `Failed to ${isEditing ? 'update' : 'create'} post`, 'error');
      }
    } catch (error) {
      showNotification(`Error ${isEditing ? 'updating' : 'creating'} post. Please try again.`, 'error');
    }
  }
}


function initializeInbox() {
  const composeBtn = document.getElementById('composeBtn');
  const composeModal = document.getElementById('composeModal');
  const closeModal = document.getElementById('closeModal');
  const cancelModal = document.getElementById('cancelModal');
  const composeForm = document.getElementById('composeForm');
  

  if (composeBtn) {
    composeBtn.addEventListener('click', () => {
      composeModal.classList.add('active');
    });
  }
  

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
  

  if (composeForm) {
    composeForm.addEventListener('submit', function(e) {
      e.preventDefault();
      submitMessage();
    });
  }
  

  window.addEventListener('click', function(event) {
    if (event.target === composeModal) {
      composeModal.classList.remove('active');
    }
  });
}

function populateInboxList() {
  const inboxList = document.getElementById('inboxList');
  if (!inboxList) return;
  
  inboxList.innerHTML = '';
  
  if (messagesData.length === 0) {
    inboxList.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">No messages yet.</p>';
    return;
  }
  
  messagesData.forEach(msg => {
    const item = createMessageItem(msg);
    inboxList.appendChild(item);
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
  
  item.addEventListener('click', async () => {
    displayMessage(msg);
    
    
    document.querySelectorAll('.message-item').forEach(m => m.classList.remove('active'));
    item.classList.add('active');
    
    
    if (!msg.read) {
      msg.read = true;
      item.style.opacity = '0.7';
      
      
      updateInboxBadge();
      
      
      const formData = new FormData();
      formData.append('action', 'mark_read');
      formData.append('message_id', msg.id);
      
      try {
        await fetch(`${API_BASE}/user_api.php`, {
          method: 'POST',
          body: formData,
          credentials: 'include'
        });
      } catch (error) {
        console.error('Failed to mark message as read:', error);
      }
    }
  });
  
  return item;
}

function displayMessage(msg) {
  const messageView = document.getElementById('messageView');
  if (!messageView) return;
  
  
  const msgDate = new Date(msg.date);
  const formattedDate = msgDate.toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
  
  messageView.innerHTML = `
    <div class="message-content">
      <div class="message-header">
        <div class="message-from">
          <i class="fas fa-user-circle" style="margin-right: 8px; color: var(--primary-green);"></i>
          From: <strong>${msg.sender}</strong> &lt;${msg.email || 'no-reply'}&gt;
        </div>
        <div class="message-subject-full">
          <i class="fas fa-envelope" style="margin-right: 8px; color: var(--primary-blue);"></i>
          ${msg.subject}
        </div>
        <div class="message-date">
          <i class="fas fa-clock" style="margin-right: 8px;"></i>
          ${formattedDate}
        </div>
      </div>
      <div class="message-body">${msg.content}</div>
      <div class="message-actions" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); display: flex; gap: 12px;">
        <button onclick="sendReply('${msg.email || ''}', 'Re: ${msg.subject}')" class="btn-reply" style="padding: 10px 20px; background: var(--primary-green); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
          <i class="fas fa-reply"></i> Reply
        </button>
        <button onclick="deleteMessage(${msg.id})" class="btn-delete-msg" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
          <i class="fas fa-trash"></i> Delete
        </button>
      </div>
    </div>
  `;
}

async function deleteMessage(messageId) {
  if (!confirm('Are you sure you want to delete this message?')) return;
  
  const formData = new FormData();
  formData.append('message_id', messageId);
  
  const result = await fetchAPI('user_api.php?action=delete_message', {
    method: 'POST',
    body: formData
  });
  
  if (result.success) {
    await loadMessages();
    populateInboxList();

    const messageView = document.getElementById('messageView');
    if (messageView) {
      messageView.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-envelope"></i>
          <p>Select a message to view</p>
        </div>
      `;
    }
    updateInboxBadge();
  } else {
    alert(result.message || 'Failed to delete message');
  }
}

function sendMessage(email, subject) {
  const recipientEl = document.getElementById('recipientEmail');
  const subjectEl = document.getElementById('messageSubject');
  const contentEl = document.getElementById('messageContent');
  const modal = document.getElementById('composeModal');
  
  if (recipientEl) recipientEl.value = email;
  if (subjectEl) subjectEl.value = `Regarding: ${subject}`;
  if (contentEl) contentEl.value = '';
  if (modal) modal.classList.add('active');
}

function sendReply(email, subject) {
  const recipientEl = document.getElementById('recipientEmail');
  const subjectEl = document.getElementById('messageSubject');
  const contentEl = document.getElementById('messageContent');
  const modal = document.getElementById('composeModal');
  
  if (recipientEl) recipientEl.value = email;
  if (subjectEl) subjectEl.value = subject;
  if (contentEl) contentEl.value = '';
  if (modal) modal.classList.add('active');
}

async function submitMessage() {
  const recipientEl = document.getElementById('recipientEmail');
  const subjectEl = document.getElementById('messageSubject');
  const contentEl = document.getElementById('messageContent');
  const form = document.getElementById('composeForm');
  const modal = document.getElementById('composeModal');
  
  const recipient = recipientEl ? recipientEl.value : '';
  const subject = subjectEl ? subjectEl.value : '';
  const content = contentEl ? contentEl.value : '';
  
  if (!recipient || !content) {
    showNotification('Recipient and message are required', 'error');
    return;
  }
  
  const formData = new FormData();
  formData.append('action', 'send_message');
  formData.append('recipient_email', recipient);
  formData.append('subject', subject);
  formData.append('message_content', content);
  
  try {
    const response = await fetch(`${API_BASE}/user_api.php`, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });
    const result = await response.json();
    
    if (result.success) {
      showNotification('Message sent successfully!', 'success');
      if (form) form.reset();
      if (modal) modal.classList.remove('active');
    } else {
      showNotification(result.message || 'Failed to send message', 'error');
    }
  } catch (error) {
    showNotification('Error sending message. Please try again.', 'error');
  }
}


function initializeSettings() {
  const settingsTabs = document.querySelectorAll('.settings-tab');
  const settingsContents = document.querySelectorAll('.settings-content');
  const profileForm = document.getElementById('profileForm');
  const accountForm = document.getElementById('accountForm');
  const preferencesForm = document.getElementById('preferencesForm');
  const profilePictureInput = document.getElementById('profilePicture');
  
  
  settingsTabs.forEach(tab => {
    tab.addEventListener('click', function() {
      const tabName = this.getAttribute('data-tab');
      
      
      settingsTabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      
      
      settingsContents.forEach(content => content.classList.remove('active'));
      const tabContent = document.getElementById(tabName + '-tab');
      if (tabContent) tabContent.classList.add('active');
    });
  });
  
  
  if (profilePictureInput) {
    profilePictureInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
          const pic = document.getElementById('currentProfilePic');
          if (pic) pic.src = event.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }
  
  
  if (profileForm) {
    profileForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      if (!validateProfileForm()) {
        return;
      }
      const formData = new FormData(this);
      formData.append('action', 'update_profile');
      try {
        const response = await fetch(`${API_BASE}/user_api.php`, {
          method: 'POST',
          body: formData,
          credentials: 'include'
        });
        const result = await response.json();
        showNotification('Profile updated successfully!', 'success');

      } catch (error) {
        showNotification('Error updating profile. Please try again.', 'error');
      }
    });
  }
  

  if (accountForm) {
    accountForm.addEventListener('submit', function(e) {
      e.preventDefault();
    });
    
    const changePasswordBtn = accountForm.querySelector('button[type="button"].btn-submit');
    if (changePasswordBtn) {
      changePasswordBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const currentPasswordEl = document.getElementById('currentPassword');
        const newPasswordEl = document.getElementById('newPassword');
        const confirmPasswordEl = document.getElementById('confirmPassword');
        
        const currentPassword = currentPasswordEl ? currentPasswordEl.value : '';
        const newPassword = newPasswordEl ? newPasswordEl.value : '';
        const confirmPassword = confirmPasswordEl ? confirmPasswordEl.value : '';
        
        
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
        
        
        const formData = new FormData();
        formData.append('action', 'change_password');
        formData.append('current_password', currentPassword);
        formData.append('new_password', newPassword);
        formData.append('confirm_password', confirmPassword);
        
        try {
          const response = await fetch(`${API_BASE}/user_api.php`, {
            method: 'POST',
            body: formData,
            credentials: 'include'
          });
          const result = await response.json();
          
          if (result.success) {
            showNotification('Password updated successfully!', 'success');
            
            if (currentPasswordEl) currentPasswordEl.value = '';
            if (newPasswordEl) newPasswordEl.value = '';
            if (confirmPasswordEl) confirmPasswordEl.value = '';
          } else {
            showNotification(result.message || 'Failed to update password', 'error');
          }
        } catch (error) {
          showNotification('Error updating password. Please try again.', 'error');
        }
      });
    }
    
    
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
            fetchAPI('user_api.php?action=delete_account', {
              method: 'POST'
            }).then(result => {
              if (result.success) {
                showNotification('Account deleted successfully', 'success');
                setTimeout(() => {
                  window.location.href = 'index.html';
                }, 2000);
              } else {
                showNotification(result.message || 'Failed to delete account', 'error');
              }
            }).catch(() => {
              showNotification('Error deleting account. Please try again.', 'error');
            });
          } else {
            showNotification('Account deletion cancelled', 'info');
          }
        }
      });
    }
    
    
  }
  

  if (profileForm) {
    profileForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      if (!validateProfileForm()) {
        return;
      }
      const formData = new FormData(this);
      try {
        const response = await fetchAPI('user_api.php?action=update_profile', {
          method: 'POST',
          body: formData
        });
        if (response.success) {
          showNotification('Your profile has been updated!', 'success');
        } else {
          showNotification(response.message || 'Failed to update profile', 'error');
        }
      } catch (error) {
        console.error('Error updating profile:', error);
      }
    });
  }

  
  if (preferencesForm) {
    preferencesForm.addEventListener('submit', function(e) {
      e.preventDefault();

 
      showNotification('Preferences saved successfully!', 'success');
    });
  }
}

function loadUserProfile() {
  fetchAPI('user_api.php?action=get_profile')
  .then(data => {
    if (data.success && data.data) {
      const profile = data.data;


      const nameField = document.getElementById('profileName');
      const studentIdField = document.getElementById('profileStudentId');
      const emailField = document.getElementById('profileEmail');
      const phoneField = document.getElementById('profilePhone');
      const universityField = document.getElementById('profileUniversity');

      if (nameField) nameField.value = profile.name || '';
      if (studentIdField) studentIdField.value = profile.student_id || '';
      if (emailField) emailField.value = profile.email || '';
      if (phoneField) phoneField.value = profile.phone || '';
      if (universityField) {

        universityField.value = profile.university || '';
      }


      const userNameDisplays = document.querySelectorAll('.user-name, .profile-name');
      const userEmailDisplays = document.querySelectorAll('.user-email, .profile-email');

      userNameDisplays.forEach(el => el.textContent = profile.name || 'User');
      userEmailDisplays.forEach(el => el.textContent = profile.email || '');
    }
  })
  .catch(error => {
    console.error('Error loading user profile:', error);
  });
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
  
  const iconClass = type === 'success' ? 'fa-check' : 
                    type === 'error' ? 'fa-times' : 'fa-info-circle';
  
  notification.innerHTML = `<i class="fas ${iconClass}"></i> ${message}`;
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  const toggleBtn = input.nextElementSibling;
  const icon = toggleBtn.querySelector('i');

  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fas fa-eye';
  }
}


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
