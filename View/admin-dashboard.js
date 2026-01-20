
const API_BASE = '../Controller';


document.addEventListener('DOMContentLoaded', function () {

  checkAdminStatus().then(isValid => {
    if (isValid) {
      initializeDashboard();
      loadData();
    }
  });
});



async function checkAdminStatus() {
  try {
    const response = await fetch(`${API_BASE}/admin_api.php?action=get_admin_profile`, {
      credentials: 'include'
    });
    const data = await response.json();

    if (!response.ok || !data.success) {
      alert(data.message || 'Your session has expired or you do not have admin access. Please login again.');
      window.location.href = '../Controller/login.php';
      return false;
    }

    return true;
  } catch (error) {
    alert('Unable to verify your session. Please login again.');
    window.location.href = '../Controller/login.php';
    return false;
  }
}

function initializeDashboard() {
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    item.addEventListener('click', function(e) {
      e.preventDefault();
      const sectionId = this.getAttribute('data-section');
      switchSection(sectionId);
    });
  });

  const userFilter = document.getElementById('user-filter');
  if (userFilter) userFilter.addEventListener('change', filterUsers);

  const postFilter = document.getElementById('post-filter');
  if (postFilter) postFilter.addEventListener('change', filterPosts);

  const createUserForm = document.getElementById('create-user-form');
  if (createUserForm) {
    createUserForm.addEventListener('submit', handleCreateUser);
  }
}

function switchSection(sectionId) {
  const sections = document.querySelectorAll('.content-section');
  sections.forEach(section => section.classList.remove('active'));

  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => item.classList.remove('active'));

  const activeSection = document.getElementById(sectionId);
  if (activeSection) activeSection.classList.add('active');

  const activeNav = document.querySelector(`[data-section="${sectionId}"]`);
  if (activeNav) activeNav.classList.add('active');
}


function loadData() {
  fetchUsers();
  fetchPosts();
  fetchDatabaseStats();
  loadDashboardStats();
  loadAdminProfile();
}

function fetchUsers() {
  fetch('../Controller/admin_api.php?action=get_users', { credentials: 'include' })
  .then(response => response.json())
  .then(data => {
    const users = data.data || data || [];
    displayUsers(users);
    updateUserStats(users);
  })
  .catch(error => {
    console.error('Error fetching users:', error);
    displayUsers([]);
  });
}

function fetchPosts() {
  fetch('../Controller/admin_api.php?action=get_posts', { credentials: 'include' })
  .then(response => response.json())
  .then(data => {
    const posts = data.data || data || [];
    displayPosts(posts);
  })
  .catch(error => {
    console.error('Error fetching posts:', error);
    displayPosts([]);
  });
}

function displayUsers(users) {
  const usersList = document.getElementById('users-list');
  if (!usersList) return;

  if (!Array.isArray(users) || users.length === 0) {
    usersList.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #999;">No users found</td></tr>';
    return;
  }

  usersList.innerHTML = users.map(user => `
    <tr>
      <td>#${user.student_id || user.id}</td>
      <td>${user.name}</td>
      <td>${user.email}</td>
      <td>
        <span class="status-badge status-${user.status}">
          ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
        </span>
      </td>
      <td>
        <span class="role-badge ${user.role === 'moderator' ? 'moderator' : ''}">
          ${user.role === 'moderator' ? 'Moderator' : 'User'}
        </span>
      </td>
      <td>
        <span class="warning-badge ${user.warning_count > 0 ? 'has-warnings' : ''}">
          ${user.warning_count || 0}
        </span>
      </td>
      <td>${user.created_at ? user.created_at.split(' ')[0] : 'N/A'}</td>
      <td>
        <div class="action-buttons" style="display: flex; flex-wrap: wrap; gap: 2px;">
          ${user.status !== 'active' ? `<button class="btn btn-success" onclick="approveUser(${user.user_id})">Approve</button>` : ''}
          ${user.status !== 'banned' ? `<button class="btn btn-danger" onclick="banUser(${user.user_id})" style="padding: 6px 10px; font-size: 14px;">Ban</button>` : ''}
          ${user.role !== 'moderator' ? `<button class="btn btn-primary" onclick="appointModerator(${user.user_id})" style="padding: 2px 4px; font-size: 14px;">make Mod</button>` : `
          <button class="btn btn-secondary" onclick="removeModerator(${user.user_id})" style="padding: 2px 3px; font-size: 14px;">remove mod</button>`}
          <button class="btn btn-danger" onclick="deleteUser(${user.user_id})" style="padding: 6px 10px; font-size: 14px;">Delete</button>
        </div>
      </td>
    </tr>
  `).join('');
}

function displayPosts(posts) {
  const postsList = document.getElementById('posts-list');
  if (!postsList) return;

  if (!Array.isArray(posts) || posts.length === 0) {
    postsList.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #999;">No posts found</td></tr>';
    return;
  }

  postsList.innerHTML = posts.map(post => `
    <tr>
      <td>#${post.post_id}</td>
      <td>${post.item_name || 'Untitled'}</td>
      <td>${post.category || 'N/A'}</td>
      <td>${post.post_type || 'N/A'}</td>
      <td>${post.user_name || 'Unknown'}</td>
      <td>
        <span class="status-badge status-${post.post_status}">
          ${post.post_status.charAt(0).toUpperCase() + post.post_status.slice(1)}
        </span>
      </td>
      <td>${post.created_at ? post.created_at.split(' ')[0] : 'N/A'}</td>
      <td>
        <div class="action-buttons">
          ${post.post_status !== 'approved' ? `<button class="btn btn-success" onclick="approvePost(${post.post_id})">Approve</button>` : ''}
          ${post.post_status !== 'banned' ? `<button class="btn btn-danger" onclick="banPost(${post.post_id})">Ban</button>` : ''}
        </div>
      </td>
    </tr>
  `).join('');
}

function filterUsers() {
  const filterEl = document.getElementById('user-filter');
  if (!filterEl) return;
  const filter = filterEl.value;
  const url = filter
    ? `../Controller/admin_api.php?action=get_users&filter=${filter}`
    : '../Controller/admin_api.php?action=get_users';

  fetch(url, { credentials: 'include' })
  .then(response => response.json())
  .then(data => {
    const users = data.data || data || [];
    displayUsers(users);
  })
  .catch(error => {
    console.error('Error filtering users:', error);
    displayUsers([]);
  });
}

function filterPosts() {
  const filterEl = document.getElementById('post-filter');
  if (!filterEl) return;
  const filter = filterEl.value;
  const url = filter
    ? `../Controller/admin_api.php?action=get_posts&filter=${filter}`
    : '../Controller/admin_api.php?action=get_posts';

  fetch(url, { credentials: 'include' })
  .then(response => response.json())
  .then(data => {
    const posts = data.data || data || [];
    displayPosts(posts);
  })
  .catch(error => {
    console.error('Error filtering posts:', error);
    displayPosts([]);
  });
}

function updateUserStats(users) {
  const totalEl = document.getElementById('total-users');
  const approvedEl = document.getElementById('approved-users');
  const bannedEl = document.getElementById('banned-users');

  if (!Array.isArray(users)) {
    if (totalEl) totalEl.textContent = '0';
    if (approvedEl) approvedEl.textContent = '0';
    if (bannedEl) bannedEl.textContent = '0';
    return;
  }

  if (totalEl) totalEl.textContent = users.length;
  if (approvedEl) approvedEl.textContent = users.filter(u => u.status === 'active').length;
  if (bannedEl) bannedEl.textContent = users.filter(u => u.status === 'banned').length;
}

function handleCreateUser(e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData();

  formData.append('action', 'create_user');
  formData.append('name', document.getElementById('user-name').value);
  formData.append('email', document.getElementById('user-email').value);
  formData.append('password', document.getElementById('user-password').value);
  formData.append('role', document.getElementById('user-role').value);
  formData.append('status', document.getElementById('user-status').value);

  fetch('../Controller/admin_api.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('User created successfully!');
      form.reset();
      fetchUsers();
    } else {
      alert('Error: ' + (data.message || 'Failed to create user'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error creating user. Please try again.');
  });
}

function loadAdminProfile() {
  fetch('../Controller/admin_api.php?action=get_admin_profile', { credentials: 'include' })
  .then(response => response.json())
  .then(data => {
    if (data.success && data.data) {
      const profile = data.data;
      document.getElementById('profile-name').textContent = profile.name || 'Admin';
      document.getElementById('profile-email').textContent = profile.email || '';
      document.getElementById('detail-name').textContent = profile.name || '-';
      document.getElementById('detail-email').textContent = profile.email || '-';
      document.getElementById('detail-joined').textContent = profile.created_at ? profile.created_at.split(' ')[0] : '-';
    }
  })
  .catch(error => {
    console.error('Error loading admin profile:', error);
  });
}

function approveUser(userId) {
  openModal('Approve User', `Are you sure you want to approve user #${userId}?`, () => {
    fetch(`../Controller/admin_api.php?action=approve_user&id=${userId}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      alert(data.message || 'User approved successfully');
      loadData();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to approve user');
    });
  });
}

function banUser(userId) {
  openModal('Ban User', `Are you sure you want to ban user #${userId}?`, () => {
    fetch(`../Controller/admin_api.php?action=ban_user&id=${userId}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      alert(data.message || 'User banned successfully');
      loadData();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to ban user');
    });
  });
}

function appointModerator(userId) {
  openModal('Appoint Moderator', `Make user #${userId} a moderator?`, () => {
    fetch(`../Controller/admin_api.php?action=appoint_moderator&id=${userId}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      alert(data.message || 'Moderator appointed successfully');
      loadData();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to appoint moderator');
    });
  });
}

function removeModerator(userId) {
  openModal('Remove Moderator', `Remove moderator status from user #${userId}?`, () => {
    fetch(`../Controller/admin_api.php?action=remove_moderator&id=${userId}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      alert(data.message || 'Moderator status removed successfully');
      loadData();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to remove moderator');
    });
  });
}

function removeWarning(userId) {
  openModal('Remove Warning', `Remove all warnings from user #${userId}?`, () => {
    fetch(`../Controller/admin_api.php?action=remove_warning&id=${userId}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      alert(data.message || 'Warnings removed successfully');
      loadData();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to remove warnings');
    });
  });
}

function deleteUser(userId) {
  openModal('Delete User', `Are you sure you want to permanently delete user #${userId}? This action cannot be undone and will delete all their posts, messages, and warnings.`, () => {
    const formData = new FormData();
    formData.append('action', 'delete_user');
    formData.append('id', userId);
    
    fetch(`../Controller/admin_api.php`, { 
      method: 'POST',
      body: formData,
      credentials: 'include' 
    })
    .then(response => response.json())
    .then(data => {
      if (data && data.success) {
        alert(data.message || 'User deleted successfully');
        loadData();
      } else {
        alert(data.message || 'Failed to delete user');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to delete user');
    });
  });
}

function openModal(title, content, confirmCallback) {
  const modal = document.getElementById('action-modal');
  const modalTitle = document.getElementById('modal-title');
  const modalBody = document.getElementById('modal-body');
  const confirmBtn = document.getElementById('modal-confirm');

  modalTitle.textContent = title;
  modalBody.innerHTML = content;
  confirmBtn.onclick = () => {
    confirmCallback();
    closeModal();
  };

  modal.classList.add('active');
}

function closeModal() {
  const modal = document.getElementById('action-modal');
  modal.classList.remove('active');
}

function confirmAction() {
}

function openProfileEditModal() {
  const modal = document.getElementById('profile-edit-modal');
  if (modal) {
    fetch('../Controller/admin_api.php?action=get_admin_profile', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.data) {
        const profile = data.data;
        document.getElementById('edit-name').value = profile.name || '';
        document.getElementById('edit-email').value = profile.email || '';
      }
    })
    .catch(error => {
      console.error('Error loading profile for edit:', error);
    });

    modal.classList.add('active');
  }
}

function closeProfileModal() {
  const modal = document.getElementById('profile-edit-modal');
  const form = document.getElementById('profile-form');
  if (modal) modal.classList.remove('active');
  if (form) form.reset();
}

function saveProfileChanges() {
  const name = document.getElementById('edit-name').value;
  const email = document.getElementById('edit-email').value;
  const password = document.getElementById('edit-password').value;
  const confirmPassword = document.getElementById('edit-confirm-password').value;

  if (!name || !email) {
    alert('Name and email are required');
    return;
  }

  if (password && password !== confirmPassword) {
    alert('Passwords do not match');
    return;
  }

  const formData = new FormData();
  formData.append('action', 'update_admin_profile');
  formData.append('name', name);
  formData.append('email', email);
  if (password) {
    formData.append('password', password);
  }

  fetch('../Controller/admin_api.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Profile updated successfully!');
      closeProfileModal();
      loadAdminProfile();
    } else {
      alert('Error: ' + (data.message || 'Failed to update profile'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error updating profile');
  });
}


function backupDatabase() {
  openModal('Backup Database', 'This will create a backup of the entire database. Continue?', () => {
    alert('Database backup functionality would be implemented here.');
  });
}

function refreshData() {
  loadData();
  alert('Data refreshed successfully!');
}

function testConnection() {
  const status = document.getElementById('db-status');
  status.textContent = 'Testing...';

  fetch('../Controller/admin_api.php?action=get_database_stats', { credentials: 'include' })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      status.textContent = 'Connected';
      status.style.color = '#149444';
    } else {
      status.textContent = 'Failed';
      status.style.color = '#ef4444';
    }
  })
  .catch(error => {
    status.textContent = 'Error';
    status.style.color = '#ef4444';
    console.error('Connection test error:', error);
  });
}






async function fetchAPI(endpoint) {
  try {
    const response = await fetch(`${API_BASE}/${endpoint}`, {
      credentials: 'include'
    });
    const data = await response.json();
    if (data.success === false) {
      console.error('API Error:', data.message);
      return data.data || [];
    }
    return data.data || data || [];
  } catch (error) {
    console.error('Fetch Error:', error);
    return [];
  }
}


function fetchUsers() {
  fetch('../Controller/admin_api.php?action=get_users', { credentials: 'include' })
  .then(response => response.json())
  .then(data => {
    const users = data.data || data || [];
    displayUsers2(users);
    updateUserStats(users);
  })
  .catch(error => {
    console.error('Error fetching users:', error);
    displayUsers2([]);
  });
}


function displayUsers2(users) {
  const usersList = document.getElementById('users-list');
  if (!usersList) return;

  if (!Array.isArray(users) || users.length === 0) {
    usersList.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">No users found</td></tr>';
    return;
  }

  usersList.innerHTML = users.map(user => `
    <tr>
      <td>#${user.user_id || user.id}</td>
      <td>${user.name}</td>
      <td>${user.email}</td>
      <td>
        <span class="status-badge status-${user.status}">
          ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
        </span>
      </td>
      <td>
        <span class="role-badge ${user.role === 'moderator' ? 'moderator' : ''}">
          ${user.role === 'moderator' ? 'Moderator' : 'User'}
        </span>
      </td>
      <td>
        <span class="warning-badge ${user.warning_count > 0 ? 'has-warnings' : ''}">
          ${user.warning_count || 0}
        </span>
      </td>
      <td>${user.created_at ? user.created_at.split(' ')[0] : 'N/A'}</td>
      <td>
        <div class="action-buttons" style="display: flex; flex-wrap: wrap; gap: 4px;">
          ${user.status !== 'active' ? `<button class="btn btn-success" onclick="approveUser(${user.user_id})">Approve</button>` : ''}
          ${user.status !== 'banned' ? `<button class="btn btn-danger" onclick="banUser(${user.user_id})" style="padding: 6px 10px; font-size: 14px;">Ban</button>` : ''}
          ${user.role !== 'moderator' ? `<button class="btn btn-primary" onclick="appointModerator(${user.user_id})" style="padding: 4px 5px; font-size: 14px;">make Mod</button>` : `<button class="btn btn-secondary" onclick="removeModerator(${user.user_id})" style="padding: 4px 5px; font-size: 14px;">remove mod</button>`}
          <button class="btn btn-danger" onclick="deleteUser(${user.user_id})" style="padding: 6px 10px; font-size: 14px;">Delete</button>
        </div>
      </td>
    </tr>
  `).join('');
}


function filterUsers() {
  const filterEl = document.getElementById('user-filter');
  if (!filterEl) return;
  const filter = filterEl.value;
  const url = filter
    ? `../Controller/admin_api.php?action=get_users&filter=${filter}`
    : '../Controller/admin_api.php?action=get_users';

  fetch(url, { credentials: 'include' })
  .then(response => response.json())
  .then(data => {
    const users = data.data || data || [];
    displayUsers2(users);
  })
  .catch(error => {
    console.error('Error filtering users:', error);
    displayUsers2([]);
  });
}


function updateUserStats(users) {
  const totalEl = document.getElementById('total-users');
  const approvedEl = document.getElementById('approved-users');
  const bannedEl = document.getElementById('banned-users');
  if (!Array.isArray(users)) {
    if (totalEl) totalEl.textContent = '0';
    if (approvedEl) approvedEl.textContent = '0';
    if (bannedEl) bannedEl.textContent = '0';
    return;
  }
  if (totalEl) totalEl.textContent = users.length;
  if (approvedEl) approvedEl.textContent = users.filter(u => u.status === 'active').length;
  if (bannedEl) bannedEl.textContent = users.filter(u => u.status === 'banned').length;
}


function approveUser(userId) {
  openModal('Approve User', `Are you sure you want to approve user #${userId}?`, () => {
    fetch(`../Controller/admin_api.php?action=approve_user&id=${userId}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      alert(data.message || 'User approved successfully');
      loadData();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to approve user');
    });
  });
}


function banUser(userId) {
  openModal('Ban User', `Are you sure you want to ban user #${userId}?`, () => {
    fetch(`../Controller/admin_api.php?action=ban_user&id=${userId}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      alert(data.message || 'User banned successfully');
      loadData();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to ban user');
    });
  });
}


function appointModerator(userId) {
  openModal('Appoint Moderator', `Make user #${userId} a moderator?`, () => {
    fetch(`../Controller/admin_api.php?action=appoint_moderator&id=${userId}`, { credentials: 'include' })
      .then(response => response.json())
      .then(data => {
        alert(data.message || 'Moderator appointed successfully');
        loadData();
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to appoint moderator');
      });
  });
}


function removeModerator(userId) {
  openModal('Remove Moderator', `Remove moderator status from user #${userId}?`, () => {
    fetch(`../Controller/admin_api.php?action=remove_moderator&id=${userId}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      alert(data.message || 'Moderator status removed successfully');
      loadData();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to remove moderator');
    });
  });
}

function approvePost(postId) {
  openModal('Approve Post', `Are you sure you want to approve post #${postId}?`, () => {
    fetch(`../Controller/admin_api.php?action=approve_post&id=${postId}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      alert(data.message || 'Post approved successfully');
      fetchPosts();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to approve post');
    });
  });
}

function banPost(postId) {
  openModal('Ban Post', `Are you sure you want to ban post #${postId}?`, () => {
    fetch(`../Controller/admin_api.php?action=ban_post&id=${postId}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      alert(data.message || 'Post banned successfully');
      fetchPosts();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to ban post');
    });
  });
}


function removeWarning(userId) {
  openModal('Remove Warning', `Remove all warnings from user #${userId}?`, () => {
    fetch(`../Controller/admin_api.php?action=remove_all_warnings&id=${userId}`, { credentials: 'include' })
      .then(response => response.json())
      .then(data => {
        alert(data.message || 'Warnings removed successfully');
        loadData();
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to remove warnings');
      });
  });
}


function fetchPosts() {
  fetch('../Controller/admin_api.php?action=get_posts', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      const posts = data.data || data || [];
      displayPosts(posts);
      updatePostStats(posts);
    })
    .catch(error => {
      console.error('Error fetching posts:', error);
      displayPosts([]);
    });
}


function displayPosts(posts) {
  const postsList = document.getElementById('posts-list');
  if (!postsList) return;

  if (!Array.isArray(posts) || posts.length === 0) {
    postsList.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #999;">No posts found</td></tr>';
    return;
  }

  postsList.innerHTML = posts.map(post => {
    const postId = post.post_id || post.id;
    const title = post.item_name || post.title || 'Untitled';
    const category = post.category || 'Other';
    const type = post.post_type || post.type || 'lost';
    const status = post.post_status || post.status || 'pending';
    const postedBy = post.user_name || post.student_name || 'N/A';
    const createdAt = post.created_at ? post.created_at.split(' ')[0] : 'N/A';
    
    return `
      <tr>
        <td>#${postId}</td>
        <td>${title}</td>
        <td>${capitalizeFirst(category)}</td>
        <td>
          <span style="color: ${type === 'lost' ? '#ef4444' : '#149444'}; font-weight: 500;">
            ${type === 'lost' ? 'Lost' : 'Found'}
          </span>
        </td>
        <td>${postedBy}</td>
        <td>
          <span class="status-badge status-${status}">
            ${capitalizeFirst(status)}
          </span>
        </td>
        <td>${createdAt}</td>
        <td>
          <div class="action-buttons">
            ${status !== 'approved' ? `<button class="btn btn-success" onclick="approvePost(${postId})">Approve</button>` : ''}
            ${status !== 'banned' ? `<button class="btn btn-danger" onclick="banPost(${postId})">Ban</button>` : ''}
            <button class="btn btn-secondary" onclick="viewPostDetails(${postId})">View</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}


function capitalizeFirst(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}


function filterPosts() {
  const filterEl = document.getElementById('post-filter');
  if (!filterEl) return;
  const filter = filterEl.value;
  const url = filter 
    ? `../Controller/admin_api.php?action=get_posts&filter=${filter}`
    : '../Controller/admin_api.php?action=get_posts';
  
  fetch(url, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      const posts = data.data || data || [];
      displayPosts(posts);
    })
    .catch(error => {
      console.error('Error filtering posts:', error);
      displayPosts([]);
    });
}


function updatePostStats(posts) {
  const pendingEl = document.getElementById('pending-posts');
  if (pendingEl && Array.isArray(posts)) {
    pendingEl.textContent = posts.filter(p => (p.post_status || p.status) === 'pending').length;
  }
}


function approvePost(postId) {
  openModal('Approve Post', `Approve post #${postId}?`, () => {
    fetch(`../Controller/admin_api.php?action=approve_post&id=${postId}`, { credentials: 'include' })
      .then(response => response.json())
      .then(data => {
        alert(data.message || 'Post approved successfully');
        loadData();
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to approve post');
      });
  });
}


function banPost(postId) {
  openModal('Ban Post', `Ban post #${postId}?`, () => {
    fetch(`../Controller/admin_api.php?action=ban_post&id=${postId}`, { credentials: 'include' })
      .then(response => response.json())
      .then(data => {
        alert(data.message || 'Post banned successfully');
        loadData();
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to ban post');
      });
  });
}


function viewPostDetails(postId) {
  fetch(`../Controller/admin_api.php?action=get_post_details&id=${postId}`, { credentials: 'include' })
    .then(response => response.json())
    .then(result => {
      if (!result.success) {
        alert(result.message || 'Failed to load post details');
        return;
      }
      const post = result.data;
      const title = post.item_name || post.title || 'Untitled';
      const description = post.item_description || post.description || 'No description';
      const category = post.category || 'Other';
      const type = post.post_type || post.type || 'lost';
      const status = post.post_status || post.status || 'pending';
      const location = post.location || 'Not specified';
      const postedBy = post.user_name || 'Unknown';
      const email = post.email || 'N/A';
      const createdAt = post.created_at || 'N/A';
      const imageUrl = post.image_url || '';
      
      const details = `
        ${imageUrl ? `<div style="text-align: center; margin-bottom: 15px;"><img src="${imageUrl}" alt="${title}" style="max-width: 100%; max-height: 200px; border-radius: 8px;"></div>` : ''}
        <p><strong>Title:</strong> ${title}</p>
        <p><strong>Description:</strong> ${description}</p>
        <p><strong>Category:</strong> ${capitalizeFirst(category)}</p>
        <p><strong>Type:</strong> <span style="color: ${type === 'lost' ? '#ef4444' : '#149444'};">${type === 'lost' ? 'Lost' : 'Found'}</span></p>
        <p><strong>Location:</strong> ${location}</p>
        <p><strong>Status:</strong> ${capitalizeFirst(status)}</p>
        <p><strong>Posted By:</strong> ${postedBy} (${email})</p>
        <p><strong>Date Posted:</strong> ${createdAt}</p>
      `;
      openModal('Post Details', details, () => {});
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to load post details');
    });
}


function loadDashboardStats() {
  fetch('../Controller/admin_api.php?action=get_dashboard_stats', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      const stats = data.data || data;
      const totalStudentsEl = document.getElementById('total-students');
      const approvedStudentsEl = document.getElementById('approved-students');
      const bannedStudentsEl = document.getElementById('banned-students');
      const pendingPostsEl = document.getElementById('pending-posts');
      
      if (totalStudentsEl) totalStudentsEl.textContent = stats.total_students || '0';
      if (approvedStudentsEl) approvedStudentsEl.textContent = stats.approved_students || '0';
      if (bannedStudentsEl) bannedStudentsEl.textContent = stats.banned_students || '0';
      if (pendingPostsEl) pendingPostsEl.textContent = stats.pending_posts || '0';
    })
    .catch(error => {
      console.error('Error fetching dashboard stats:', error);
    });
}


function fetchDatabaseStats() {
  fetch('../Controller/admin_api.php?action=get_database_stats', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      const stats = data.data || data;
      const studentsCountEl = document.getElementById('students-count');
      const postsCountEl = document.getElementById('posts-count');
      
      if (studentsCountEl) studentsCountEl.textContent = stats.students_count || '0';
      if (postsCountEl) postsCountEl.textContent = stats.posts_count || '0';
    })
    .catch(error => {
      console.error('Error fetching database stats:', error);
    });
}


function viewTable(tableName) {
  const modal = document.getElementById('table-view');
  const title = document.getElementById('view-title');
  const table = document.getElementById('view-table');

  title.textContent = `${tableName.charAt(0).toUpperCase() + tableName.slice(1)} Table`;

  fetch(`../Controller/admin_api.php?action=get_${tableName}`, { credentials: 'include' })
  .then(response => response.json())
  .then(data => {
    if (tableName === 'students') {
      const headers = ['ID', 'Name', 'Email', 'Status', 'Role', 'Warnings', 'Joined'];
      const rows = data.map(row => [
        row.student_id,
        row.name,
        row.email,
        row.status,
        row.role,
        row.warning_count || 0,
        row.created_at ? row.created_at.split(' ')[0] : 'N/A'
      ]);

      table.innerHTML = `
        <thead>
          <tr>
            ${headers.map(h => `<th>${h}</th>`).join('')}
          </tr>
        </thead>
        <tbody>
          ${rows.map(row => `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`).join('')}
        </tbody>
      `;
    } else if (tableName === 'warnings') {
      const headers = ['ID', 'User', 'Email', 'Reason', 'Date', 'Status'];
      const rows = data.map(row => [
        row.warning_id,
          row.user_name || 'N/A',
          row.email || 'N/A',
          row.warning_reason || 'N/A',
          row.warning_date ? row.warning_date.split(' ')[0] : 'N/A',
          row.removed_at ? 'Removed' : 'Active'
        ]);

        table.innerHTML = `
          <thead>
            <tr>
              ${headers.map(h => `<th>${h}</th>`).join('')}
            </tr>
          </thead>
          <tbody>
            ${rows.map(row => `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`).join('')}
          </tbody>
        `;
      } else if (tableName === 'messages') {
        const headers = ['ID', 'From', 'To', 'Subject', 'Date', 'Status'];
        const rows = data.map(row => [
          row.message_id,
          row.sender_name || 'N/A',
          row.receiver_name || 'N/A',
          row.subject || 'N/A',
          row.sent_at ? row.sent_at.split(' ')[0] : 'N/A',
          row.is_read ? 'Read' : 'Unread'
        ]);

        table.innerHTML = `
          <thead>
            <tr>
              ${headers.map(h => `<th>${h}</th>`).join('')}
            </tr>
          </thead>
          <tbody>
            ${rows.map(row => `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`).join('')}
          </tbody>
        `;
      }

      modal.style.display = 'block';
    })
    .catch(error => {
      console.error('Error fetching table data:', error);
      alert('Failed to load table data');
    });
}


function closeTableView() {
  document.getElementById('table-view').style.display = 'none';
}


function viewUserData(userId, userName) {
  fetch(`../Controller/admin_api.php?action=get_user_data&user_id=${userId}`, { credentials: 'include' })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      const userData = data.data;
      const modal = document.getElementById('table-view');
      const title = document.getElementById('view-title');
      const table = document.getElementById('view-table');

      title.textContent = `Data for ${userName}`;

      let html = '<div style="max-height: 600px; overflow-y: auto;">';

      
      html += '<h3 style="color: var(--blue); margin-top: 20px;">Profile Information</h3>';
      if (userData.profile) {
        html += `
          <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">ID:</td><td style="padding: 8px; border: 1px solid #ddd;">${userData.profile.student_id}</td></tr>
            <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Name:</td><td style="padding: 8px; border: 1px solid #ddd;">${userData.profile.name}</td></tr>
            <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Email:</td><td style="padding: 8px; border: 1px solid #ddd;">${userData.profile.email}</td></tr>
            <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Role:</td><td style="padding: 8px; border: 1px solid #ddd;">${userData.profile.user_type}</td></tr>
            <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Status:</td><td style="padding: 8px; border: 1px solid #ddd;">${userData.profile.account_status}</td></tr>
            <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">University:</td><td style="padding: 8px; border: 1px solid #ddd;">${userData.profile.university || 'N/A'}</td></tr>
            <tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Joined:</td><td style="padding: 8px; border: 1px solid #ddd;">${userData.profile.created_at ? userData.profile.created_at.split(' ')[0] : 'N/A'}</td></tr>
          </table>
        `;
      }

      
      html += `<h3 style="color: var(--blue); margin-top: 20px;">Posts (${userData.posts.length})</h3>`;
      if (userData.posts.length > 0) {
        html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
        html += '<tr><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">ID</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Title</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Type</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Status</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Date</th></tr>';
        userData.posts.forEach(post => {
          html += `<tr>
            <td style="padding: 8px; border: 1px solid #ddd;">${post.post_id}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${post.item_name || 'N/A'}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${post.post_type}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${post.post_status}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${post.created_at ? post.created_at.split(' ')[0] : 'N/A'}</td>
          </tr>`;
        });
        html += '</table>';
      } else {
        html += '<p>No posts found.</p>';
      }

      
      html += `<h3 style="color: var(--blue); margin-top: 20px;">Warnings (${userData.warnings.length})</h3>`;
      if (userData.warnings.length > 0) {
        html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
        html += '<tr><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">ID</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Reason</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Date</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Status</th></tr>';
        userData.warnings.forEach(warning => {
          html += `<tr>
            <td style="padding: 8px; border: 1px solid #ddd;">${warning.warning_id}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${warning.warning_reason}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${warning.warning_date ? warning.warning_date.split(' ')[0] : 'N/A'}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${warning.removed_at ? 'Removed' : 'Active'}</td>
          </tr>`;
        });
        html += '</table>';
      } else {
        html += '<p>No warnings found.</p>';
      }

      
      const totalMessages = userData.sent_messages.length + userData.received_messages.length;
      html += `<h3 style="color: var(--blue); margin-top: 20px;">Messages (${totalMessages})</h3>`;

      if (userData.sent_messages.length > 0) {
        html += `<h4 style="margin-top: 15px;">Sent Messages (${userData.sent_messages.length})</h4>`;
        html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
        html += '<tr><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">To</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Subject</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Date</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Status</th></tr>';
        userData.sent_messages.forEach(msg => {
          html += `<tr>
            <td style="padding: 8px; border: 1px solid #ddd;">${msg.receiver_name} (${msg.receiver_email})</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${msg.subject}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${msg.sent_at ? msg.sent_at.split(' ')[0] : 'N/A'}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${msg.is_read ? 'Read' : 'Unread'}</td>
          </tr>`;
        });
        html += '</table>';
      }

      if (userData.received_messages.length > 0) {
        html += `<h4 style="margin-top: 15px;">Received Messages (${userData.received_messages.length})</h4>`;
        html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
        html += '<tr><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">From</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Subject</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Date</th><th style="padding: 8px; border: 1px solid #ddd; background: #f5f5f5;">Status</th></tr>';
        userData.received_messages.forEach(msg => {
          html += `<tr>
            <td style="padding: 8px; border: 1px solid #ddd;">${msg.sender_name} (${msg.sender_email})</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${msg.subject}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${msg.sent_at ? msg.sent_at.split(' ')[0] : 'N/A'}</td>
            <td style="padding: 8px; border: 1px solid #ddd;">${msg.is_read ? 'Read' : 'Unread'}</td>
          </tr>`;
        });
        html += '</table>';
      }

      if (totalMessages === 0) {
        html += '<p>No messages found.</p>';
      }

      html += '</div>';

      table.innerHTML = html;
      modal.style.display = 'block';
    } else {
      alert('Failed to load user data');
    }
  })
  .catch(error => {
    console.error('Error fetching user data:', error);
    alert('Failed to load user data');
  });
}


function downloadTableCSV(tableName) {
  fetch(`../Controller/admin_api.php?action=get_${tableName}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      let headers = [];
      let csvRows = [];

      if (tableName === 'students') {
        headers = ['ID', 'Name', 'Email', 'Status', 'Role', 'Joined'];
        csvRows = data.map(row => [
          row.student_id,
          row.name,
          row.email,
          row.status,
          row.role,
          row.created_at ? row.created_at.split(' ')[0] : 'N/A'
        ]);
      } else if (tableName === 'posts') {
        headers = ['ID', 'Title', 'Category', 'Type', 'Status', 'Date'];
        csvRows = data.map(row => [
          row.post_id,
          row.title,
          row.category,
          row.type,
          row.status,
          row.created_at ? row.created_at.split(' ')[0] : 'N/A'
        ]);
      } else if (tableName === 'warnings') {
        headers = ['ID', 'User', 'Email', 'Reason', 'Date', 'Status'];
        csvRows = data.map(row => [
          row.warning_id,
          row.user_name || 'N/A',
          row.email || 'N/A',
          row.warning_reason || 'N/A',
          row.warning_date ? row.warning_date.split(' ')[0] : 'N/A',
          row.removed_at ? 'Removed' : 'Active'
        ]);
      } else if (tableName === 'messages') {
        headers = ['ID', 'From', 'To', 'Subject', 'Date', 'Status'];
        csvRows = data.map(row => [
          row.message_id,
          row.sender_name || 'N/A',
          row.receiver_name || 'N/A',
          row.subject || 'N/A',
          row.sent_at ? row.sent_at.split(' ')[0] : 'N/A',
          row.is_read ? 'Read' : 'Unread'
        ]);
      }

      
      let csv = headers.join(',') + '\n';
      csvRows.forEach(row => {
        csv += row.map(cell => `"${cell}"`).join(',') + '\n';
      });

      
      const link = document.createElement('a');
      link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
      link.download = `${tableName}_${new Date().toISOString().split('T')[0]}.csv`;
      link.click();
    })
    .catch(error => {
      console.error('Error downloading CSV:', error);
      alert('Failed to download CSV');
    });
}


function backupDatabase() {
  openModal('Backup Database', 'This will create a backup of the entire database. Continue?', () => {
    fetch('../Controller/admin_api.php?action=backup_database', { credentials: 'include' })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert(`Backup created successfully: ${data.file}`);
        } else {
          alert('Failed to create backup: ' + data.error);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to backup database');
      });
  });
}


function testConnection() {
  const status = document.getElementById('db-status');
  status.textContent = 'Testing...';
  
  fetch('../Controller/admin_api.php?action=get_database_stats', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      status.textContent = 'Connected';
      status.style.color = '#149444';
      alert('Database connection successful!');
    })
    .catch(error => {
      console.error('Error:', error);
      status.textContent = 'Failed';
      status.style.color = '#ef4444';
      alert('Database connection failed!');
    });
}


function refreshData() {
  loadData();
  alert('Data refreshed successfully!');
}


function openModal(title, content, callback) {
  const modal = document.getElementById('action-modal');
  const modalTitle = document.getElementById('modal-title');
  const modalBody = document.getElementById('modal-body');
  const modalConfirm = document.getElementById('modal-confirm');

  modalTitle.textContent = title;
  modalBody.innerHTML = `<p>${content}</p>`;

  
  modalConfirm.onclick = () => {
    callback();
    closeModal();
  };

  modal.classList.add('active');
}

function closeModal() {
  document.getElementById('action-modal').classList.remove('active');
}


document.addEventListener('click', function (e) {
  const modal = document.getElementById('action-modal');
  if (e.target === modal) {
    closeModal();
  }
});




function loadAdminProfile() {
  fetch('../Controller/admin_api.php?action=get_admin_profile', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      const profile = data.data || data;
      if (profile) {
        const profileName = document.getElementById('profile-name');
        const profileEmail = document.getElementById('profile-email');
        const headerName = document.getElementById('header-profile-name');
        const detailName = document.getElementById('detail-name');
        const detailEmail = document.getElementById('detail-email');
        const detailJoined = document.getElementById('detail-joined');
        
        if (profileName) profileName.textContent = profile.name || 'Admin';
        if (profileEmail) profileEmail.textContent = profile.email || 'admin@trackback.com';
        if (headerName) headerName.textContent = profile.name || 'Admin';
        if (detailName) detailName.textContent = profile.name || '-';
        if (detailEmail) detailEmail.textContent = profile.email || '-';
        if (detailJoined) detailJoined.textContent = profile.created_at ? new Date(profile.created_at).toLocaleDateString() : '-';
      }
    })
    .catch(error => {
      console.error('Error loading admin profile:', error);
    });
}


function openProfileEditModal() {
  fetch('../Controller/admin_api.php?action=get_admin_profile', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      const profile = data.data || data;
      if (profile) {
        const editName = document.getElementById('edit-name');
        const editEmail = document.getElementById('edit-email');
        const editPassword = document.getElementById('edit-password');
        const editConfirm = document.getElementById('edit-confirm-password');
        const modal = document.getElementById('profile-edit-modal');
        
        if (editName) editName.value = profile.name || '';
        if (editEmail) editEmail.value = profile.email || '';
        if (editPassword) editPassword.value = '';
        if (editConfirm) editConfirm.value = '';
        if (modal) modal.classList.add('active');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to load profile data');
    });
}


function closeProfileModal() {
  document.getElementById('profile-edit-modal').classList.remove('active');
}


function saveProfileChanges() {
  const name = document.getElementById('edit-name').value.trim();
  const email = document.getElementById('edit-email').value.trim();
  const password = document.getElementById('edit-password').value.trim();
  const confirmPassword = document.getElementById('edit-confirm-password').value.trim();

  if (!name || !email) {
    alert('Please fill in all required fields');
    return;
  }

  if (password && password !== confirmPassword) {
    alert('Passwords do not match');
    return;
  }

  const formData = new FormData();
  formData.append('action', 'update_admin_profile');
  formData.append('name', name);
  formData.append('email', email);
  if (password) {
    formData.append('password', password);
  }

  fetch('../Controller/admin_api.php', {
    method: 'POST',
    credentials: 'include',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Profile updated successfully');
        closeProfileModal();
        loadAdminProfile();
      } else {
        alert('Error: ' + (data.error || 'Failed to update profile'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to update profile');
    });
}


document.addEventListener('click', function (e) {
  const modal = document.getElementById('profile-edit-modal');
  if (e.target === modal) {
    closeProfileModal();
  }
});

function handleLogout() {
  fetch('../Controller/auth_api.php?action=logout', {
    method: 'POST',
    credentials: 'include'
  })
  .then(res => res.json())
  .then(() => {
    window.location.href = './index.html';
  })
  .catch(err => {
    console.error('Logout error:', err);
    window.location.href = './index.html';
  });
}

const adminLogoutBtn = document.getElementById('logoutBtn');
if (adminLogoutBtn) adminLogoutBtn.addEventListener('click', function(e){ e.preventDefault(); handleLogout(); });
