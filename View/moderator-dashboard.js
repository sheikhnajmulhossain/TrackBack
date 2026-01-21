
const API_BASE = '../Controller';


document.addEventListener('DOMContentLoaded', function () {
  
  checkModeratorStatus().then(isValid => {
    if (isValid) {
      initializeDashboard();
      loadData();
    }
  });
});

function handleLogoutModerator() {
  fetch('../Controller/auth_api.php?action=logout', {
    method: 'POST',
    credentials: 'include'
  })
  .then(res => res.json())
  .then(() => window.location.href = '../index.html')
  .catch(() => window.location.href = '../index.html');
}

const modLogoutBtn = document.getElementById('logoutBtn');
if (modLogoutBtn) modLogoutBtn.addEventListener('click', function(e){ e.preventDefault(); handleLogoutModerator(); });


async function checkModeratorStatus() {
  try {
    const response = await fetch(`${API_BASE}/moderator_api.php?action=get_moderator_profile`, {
      credentials: 'include'
    });
    const data = await response.json();
    
    if (!response.ok || !data.success) {
      alert(data.message || 'Your session has expired or you do not have moderator access. Please login again.');
      window.location.href = '../login.html';
      return false;
    }
    
    return true;
  } catch (error) {
    console.error('Status check error:', error);
    alert('Unable to verify your session. Please login again.');
    window.location.href = '../login.html';
    return false;
  }
}

function initializeDashboard() {
  
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      const sectionId = item.getAttribute('data-section');
      switchSection(sectionId);
    });
  });

  
  document.getElementById('flag-filter')?.addEventListener('change', () => {
    filterFlags(document.getElementById('flag-filter').value);
  });

  document.getElementById('dispute-filter')?.addEventListener('change', () => {
    filterDisputes(document.getElementById('dispute-filter').value);
  });

  document.getElementById('watchposts-filter')?.addEventListener('change', handleWatchPostsFilter);

  
  document.getElementById('action-modal')?.addEventListener('click', function (e) {
    if (e.target === this) closeModal();
  });

  document.getElementById('warn-modal')?.addEventListener('click', function (e) {
    if (e.target === this) closeWarnModal();
  });

  document.getElementById('flag-modal')?.addEventListener('click', function (e) {
    if (e.target === this) closeFlagModal();
  });

  document.getElementById('dispute-modal')?.addEventListener('click', function (e) {
    if (e.target === this) closeDisputeModal();
  });

  document.getElementById('profile-edit-modal')?.addEventListener('click', function (e) {
    if (e.target === this) closeProfileModal();
  });
}

function switchSection(sectionId) {
  
  const sections = document.querySelectorAll('.content-section');
  sections.forEach(section => section.classList.remove('active'));

  
  document.getElementById(sectionId).classList.add('active');

  
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => item.classList.remove('active'));
  const activeNav = document.querySelector(`[data-section="${sectionId}"]`);
  if (activeNav) activeNav.classList.add('active');
}

function loadData() {
  loadDashboardStats();
  loadWarnings();
  loadFlags();
  loadDisputes();
  loadModeratorProfile();
  loadWatchPosts();
}


function loadDashboardStats() {
  fetch('../Controller/moderator_api.php?action=get_dashboard_stats', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      const stats = data.data || data;
      const totalWarningsEl = document.getElementById('total-warnings');
      const totalFlaggedEl = document.getElementById('total-flagged');
      const totalDisputesEl = document.getElementById('total-disputes');
      const totalPostsEl = document.getElementById('total-posts');

      if (totalWarningsEl) totalWarningsEl.textContent = stats.total_warnings || 0;
      if (totalFlaggedEl) totalFlaggedEl.textContent = stats.total_flagged || 0;
      if (totalDisputesEl) totalDisputesEl.textContent = stats.total_disputes || 0;
      if (totalPostsEl) totalPostsEl.textContent = stats.total_posts || 0;
    })
    .catch(error => console.error('Error loading stats:', error));
}


function loadWarnings() {
  fetch('../Controller/moderator_api.php?action=get_warnings', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      displayWarnings(data.data || data);
    })
    .catch(error => console.error('Error loading warnings:', error));
}

function displayWarnings(warnings) {
  const tbody = document.getElementById('warnings-list');
  if (!tbody) return;
  
  if (!warnings || warnings.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">No warnings issued yet</td></tr>';
    return;
  }

  tbody.innerHTML = warnings.map(warning => `
    <tr>
      <td>${warning.student_id}</td>
      <td>${warning.student_name}</td>
      <td>${warning.email}</td>
      <td><span class="status-badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">${warning.warning_count || 0}</span></td>
      <td>${warning.last_warning || '-'}</td>
      <td>${warning.reason || '-'}</td>
      <td>
        <button class="btn btn-secondary btn-sm" onclick="editWarning(${warning.id})">Edit</button>
        <button class="btn btn-danger btn-sm" onclick="removeWarning(${warning.id})">Remove</button>
      </td>
    </tr>
  `).join('');
}

function openWarnModal() {
  
  fetch('../Controller/moderator_api.php?action=get_students', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      const students = data.data || data || [];
      const select = document.getElementById('warn-student-id');
      select.innerHTML = '<option value="">Choose a student...</option>';
      if (students && students.length > 0) {
        select.innerHTML += students.map(student => `
          <option value="${student.student_id}">${student.name} (${student.email})</option>
        `).join('');
      }
    })
    .catch(error => console.error('Error loading students:', error));

  document.getElementById('warn-modal').classList.add('active');
}

function closeWarnModal() {
  document.getElementById('warn-modal').classList.remove('active');
  document.getElementById('warn-form').reset();
}

function submitWarning() {
  const form = document.getElementById('warn-form');
  const studentId = document.getElementById('warn-student-id').value;
  const reason = document.getElementById('warn-reason').value;
  const details = document.getElementById('warn-details').value;

  if (!studentId || !reason) {
    alert('Please fill in all required fields');
    return;
  }

  
  const fullReason = details ? `${reason}: ${details}` : reason;

  const formData = new FormData();
  formData.append('action', 'issue_warning');
  formData.append('user_id', studentId);
  formData.append('reason', fullReason);

  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Warning issued successfully');
        closeWarnModal();
        loadWarnings();
        loadDashboardStats();
      } else {
        alert('Error: ' + (data.message || 'Failed to issue warning'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error issuing warning');
    });
}

function editWarning(warningId) {
  alert('Edit warning: ' + warningId);
}

function removeWarning(warningId) {
  if (!confirm('Are you sure you want to remove this warning?')) return;

  const formData = new FormData();
  formData.append('action', 'remove_warning');
  formData.append('warning_id', warningId);

  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Warning removed');
        loadWarnings();
        loadDashboardStats();
      } else {
        alert('Error removing warning');
      }
    })
    .catch(error => console.error('Error:', error));
}


function loadFlags() {
  fetch('../Controller/moderator_api.php?action=get_flags', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      displayFlags(data.data || data);
    })
    .catch(error => console.error('Error loading flags:', error));
}

function displayFlags(flags) {
  const tbody = document.getElementById('flags-list');
  if (!tbody) return;
  
  if (!flags || flags.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">No flagged posts</td></tr>';
    return;
  }

  tbody.innerHTML = flags.map(flag => {
    const postId = flag.post_id;
    const title = flag.item_name || 'Untitled';
    const postedBy = flag.posted_by || 'Unknown';
    const reason = flag.flag_reason || '-';
    const status = flag.post_status || 'pending';
    const flagDate = flag.flagged_at ? flag.flagged_at.split(' ')[0] : '-';
    
    return `
      <tr>
        <td>#${postId}</td>
        <td>${title}</td>
        <td>${postedBy}</td>
        <td>${reason}</td>
        <td><span class="status-badge status-${status}">${capitalizeFirst(status)}</span></td>
        <td>${flagDate}</td>
        <td>
          <button class="btn btn-secondary btn-sm" onclick="reviewFlag(${flag.flag_id})">Review</button>
          <button class="btn btn-danger btn-sm" onclick="removePost(${postId})">Ban</button>
        </td>
      </tr>
    `;
  }).join('');
}

function capitalizeFirst(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function filterFlags(status) {
  if (!status) {
    loadFlags();
    return;
  }

  fetch(`../Controller/moderator_api.php?action=get_flags&status=${status}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => displayFlags(data.data || data))
    .catch(error => console.error('Error filtering flags:', error));
}

function reviewFlag(flagId) {
  
  fetch(`../Controller/moderator_api.php?action=get_flag_details&flag_id=${flagId}`, { credentials: 'include' })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      const flag = data.data;
      const modal = document.getElementById('flag-modal');
      if (modal) {
        modal.classList.add('active');
        modal.setAttribute('data-flag-id', flagId);
        modal.setAttribute('data-post-id', flag.post_id);

        
        document.getElementById('flag-reason').value = flag.flag_reason || '';
        
      }
    } else {
      alert('Failed to load flag details');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error loading flag details');
  });
}

function closeFlagModal() {
  const modal = document.getElementById('flag-modal');
  const form = document.getElementById('flag-form');
  if (modal) {
    modal.classList.remove('active');
    modal.removeAttribute('data-flag-id');
    modal.removeAttribute('data-post-id');
  }
  if (form) form.reset();
}

function submitFlag() {
  const flagId = document.getElementById('flag-modal').getAttribute('data-flag-id');
  const reason = document.getElementById('flag-reason').value;
  const severity = document.getElementById('flag-severity').value;
  const notes = document.getElementById('flag-notes').value;

  if (!reason || !severity) {
    alert('Please fill in all required fields');
    return;
  }

  const formData = new FormData();
  formData.append('action', 'update_flag');
  formData.append('flag_id', flagId);
  formData.append('reason', reason);
  formData.append('severity', severity);
  formData.append('notes', notes);

  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Flag updated successfully');
      closeFlagModal();
      loadFlags();
      loadDashboardStats();
    } else {
      alert('Error: ' + (data.message || 'Failed to update flag'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error updating flag');
  });
}

function unflagPost() {
  const postId = document.getElementById('flag-modal').getAttribute('data-post-id');

  if (!postId || !confirm('Are you sure you want to unflag this post?')) return;

  const formData = new FormData();
  formData.append('action', 'unflag_post');
  formData.append('post_id', postId);

  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Post unflagged successfully');
      closeFlagModal();
      loadFlags();
      loadDashboardStats();
    } else {
      alert('Error: ' + (data.message || 'Failed to unflag post'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error unflagging post');
  });
}

function removePost(postId) {
  if (!confirm('Are you sure you want to remove this post?')) return;

  const formData = new FormData();
  formData.append('action', 'remove_post');
  formData.append('post_id', postId);

  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Post removed');
        loadFlags();
        loadDashboardStats();
      } else {
        alert('Error removing post');
      }
    })
    .catch(error => console.error('Error:', error));
}


function loadDisputes() {
  fetch('../Controller/moderator_api.php?action=get_disputes', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      displayDisputes(data.data || data);
    })
    .catch(error => console.error('Error loading disputes:', error));
}

function displayDisputes(disputes) {
  const tbody = document.getElementById('disputes-list');
  if (!tbody) return;
  
  if (!disputes || disputes.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">No open disputes</td></tr>';
    return;
  }

  tbody.innerHTML = disputes.map(dispute => {
    const disputeId = dispute.dispute_id;
    const claimant1 = dispute.claimant1_name || 'Unknown';
    const claimant2 = dispute.claimant2_name || 'Unknown';
    const issue = dispute.dispute_description || '-';
    const status = dispute.dispute_status || 'open';
    const createdDate = dispute.created_at ? dispute.created_at.split(' ')[0] : '-';
    
    return `
      <tr>
        <td>#${disputeId}</td>
        <td>${claimant1}</td>
        <td>${claimant2}</td>
        <td>${issue.length > 50 ? issue.substring(0, 50) + '...' : issue}</td>
        <td><span class="status-badge status-${status}">${capitalizeFirst(status)}</span></td>
        <td>${createdDate}</td>
        <td>
          <button class="btn btn-secondary btn-sm" onclick="openDisputeModal(${disputeId})">Resolve</button>
        </td>
      </tr>
    `;
  }).join('');
}

function filterDisputes(status) {
  if (!status) {
    loadDisputes();
    return;
  }

  fetch(`../Controller/moderator_api.php?action=get_disputes&status=${status}`, { credentials: 'include' })
    .then(response => response.json())
    .then(data => displayDisputes(data.data || data))
    .catch(error => console.error('Error filtering disputes:', error));
}

function openDisputeModal(disputeId) {
  document.getElementById('dispute-modal').classList.add('active');
  document.getElementById('dispute-modal').setAttribute('data-dispute-id', disputeId);
}

function closeDisputeModal() {
  document.getElementById('dispute-modal').classList.remove('active');
  document.getElementById('dispute-form').reset();
  document.getElementById('dispute-modal').removeAttribute('data-dispute-id');
}

function submitDisputeResolution() {
  const disputeId = document.getElementById('dispute-modal').getAttribute('data-dispute-id');
  const resolution = document.getElementById('dispute-resolution').value;
  const decision = document.getElementById('dispute-decision').value;

  if (!resolution || !decision) {
    alert('Please fill in all required fields');
    return;
  }

  const formData = new FormData();
  formData.append('action', 'resolve_dispute');
  formData.append('dispute_id', disputeId);
  formData.append('resolution', resolution);
  formData.append('decision', decision);

  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Dispute resolved successfully');
        closeDisputeModal();
        loadDisputes();
        loadDashboardStats();
      } else {
        alert('Error: ' + (data.message || 'Failed to resolve dispute'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error resolving dispute');
    });
}


function loadModeratorProfile() {
  fetch('../Controller/moderator_api.php?action=get_moderator_profile', { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      const profile = data.data || data;
      if (profile) {
        const profileName = document.getElementById('profile-name');
        const profileEmail = document.getElementById('profile-email');
        const detailName = document.getElementById('detail-name');
        const detailEmail = document.getElementById('detail-email');
        const detailJoined = document.getElementById('detail-joined');
        const headerName = document.getElementById('header-profile-name');
        const editName = document.getElementById('edit-name');
        const editEmail = document.getElementById('edit-email');
        
        if (profileName) profileName.textContent = profile.name || 'Moderator';
        if (profileEmail) profileEmail.textContent = profile.email || 'email@example.com';
        if (detailName) detailName.textContent = profile.name || '-';
        if (detailEmail) detailEmail.textContent = profile.email || '-';
        if (detailJoined) detailJoined.textContent = profile.created_at || '-';
        if (headerName) headerName.textContent = profile.name || 'Moderator';
        if (editName) editName.value = profile.name || '';
        if (editEmail) editEmail.value = profile.email || '';
      }
    })
    .catch(error => console.error('Error loading profile:', error));
}

function openProfileEditModal() {
  document.getElementById('profile-edit-modal').classList.add('active');
}

function closeProfileModal() {
  document.getElementById('profile-edit-modal').classList.remove('active');
  document.getElementById('profile-form').reset();
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
  formData.append('action', 'update_moderator_profile');
  formData.append('name', name);
  formData.append('email', email);
  if (password) {
    formData.append('password', password);
  }

  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    body: formData,
    credentials: 'include'
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Profile updated successfully');
        closeProfileModal();
        loadModeratorProfile();
      } else {
        alert('Error: ' + (data.message || 'Failed to update profile'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error updating profile');
    });
}


function openModal() {
  document.getElementById('action-modal').classList.add('active');
}

function closeModal() {
  document.getElementById('action-modal').classList.remove('active');
}

function confirmAction() {
  console.log('Action confirmed');
  closeModal();
}

function refreshData() {
  loadData();
  alert('Data refreshed');
}


function loadWatchPosts(filters = {}) {
  let url = '../Controller/moderator_api.php?action=get_all_posts';
  
  if (filters.status && filters.status !== 'all') {
    url += `&status=${filters.status}`;
  }
  if (filters.type && filters.type !== 'all') {
    url += `&type=${filters.type}`;
  }
  if (filters.flagged) {
    url += `&flagged=1`;
  }
  
  fetch(url, { credentials: 'include' })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        displayWatchPosts(data.data);
      } else {
        console.error('Error loading posts:', data.message);
      }
    })
    .catch(error => console.error('Error loading posts:', error));
}

function displayWatchPosts(posts) {
  const grid = document.getElementById('watchPostsGrid');
  if (!grid) return;
  
  if (!posts || posts.length === 0) {
    grid.innerHTML = '<div class="no-data"><i class="fas fa-inbox"></i><p>No posts found</p></div>';
    return;
  }
  
  grid.innerHTML = posts.map(post => createWatchPostCard(post)).join('');
}

function createWatchPostCard(post) {
  const badgeClass = post.post_type === 'lost' ? 'post-badge--lost' : 'post-badge--found';
  const badgeText = post.post_type === 'lost' ? 'LOST' : 'FOUND';
  const flagIcon = post.flagged == 1 ? '<span class="post-flag-icon" title="This post has been flagged" style="position: absolute; top: 8px; right: 8px; background: #ef4444; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;"><i class="fas fa-flag"></i></span>' : '';
  
  
  const flagButton = post.flagged == 1
    ? `<button class="btn-contact btn-unflag" onclick="event.stopPropagation(); unflagPost(${post.post_id})"><i class="fas fa-times"></i> Unflag</button>`
    : `<button class="btn-contact btn-flag" onclick="event.stopPropagation(); openFlagModal2(${post.post_id}, '${escapeJs(post.item_name)}')"><i class="fas fa-flag"></i> Flag</button>`;
  
  const warnButton = `<button class="btn-contact btn-warn" onclick="event.stopPropagation(); openWarnModal2(${post.user_id}, '${escapeJs(post.item_name)}')"><i class="fas fa-exclamation-triangle"></i> Warn</button>`;
  
  const placeholderDataUri = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2U1ZTdlYiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM5Y2EzYWYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5ObyBJbWFnZTwvdGV4dD48L3N2Zz4=';
  const imageUrl = post.image_url 
    ? `./${post.image_url}` 
    : placeholderDataUri;
  
  return `
    <div class="post-card" data-post-id="${post.post_id}" onclick="viewPostDetails(${post.post_id})" style="cursor: pointer;">
      <div style="position: relative;">
        <img src="${imageUrl}" alt="${escapeHtml(post.item_name)}" class="post-image" onerror="this.onerror=null; this.src='${placeholderDataUri}'">
        <span class="post-badge ${badgeClass}">${badgeText}</span>
        ${flagIcon}
      </div>
      <div class="post-content">
        <div class="post-header">
          <h3 class="post-title">${escapeHtml(post.item_name)}</h3>
          <span class="post-category">${capitalizeFirst(post.category || 'N/A')}</span>
        </div>
        <p class="post-description">${escapeHtml(post.item_description || '')}</p>
        <div class="post-meta">
          <div class="post-meta-row">
            <div class="post-meta-item">
              <i class="fas fa-map-marker-alt"></i>
              <span>${escapeHtml(post.location || 'N/A')}</span>
            </div>
            <div class="post-meta-item">
              <i class="fas fa-calendar"></i>
              <span>${formatDate(post.created_at)}</span>
            </div>
          </div>
          <div class="post-meta-row">
            <div class="post-meta-item">
              <i class="fas fa-user"></i>
              <span>${escapeHtml(post.user_name || 'N/A')}</span>
            </div>
            <div class="post-meta-item">
              <i class="fas fa-info-circle"></i>
              <span class="status-badge status-${post.post_status || 'pending'}">${capitalizeFirst(post.post_status || 'pending')}</span>
            </div>
          </div>
        </div>
        <div class="post-actions">
          ${flagButton}
          ${warnButton}
        </div>
      </div>
    </div>
  `;
}


function openFlagModal2(postId, itemName) {
  const modal = document.getElementById('flag-modal2');
  if (modal) {
    modal.classList.add('active');
    modal.setAttribute('data-post-id', postId);
    document.getElementById('flag-modal2-post-name').textContent = itemName;
  }
}

function closeFlagModal2() {
  const modal = document.getElementById('flag-modal2');
  const form = document.getElementById('flag-form2');
  if (modal) {
    modal.classList.remove('active');
    modal.removeAttribute('data-post-id');
  }
  if (form) form.reset();
}

function submitFlag2() {
  const modal = document.getElementById('flag-modal2');
  const postId = modal.getAttribute('data-post-id');
  const reason = document.getElementById('flag-reason2').value;
  const details = document.getElementById('flag-details2').value;

  if (!reason) {
    alert('Please select a reason');
    return;
  }

  const fullReason = details ? `${reason}: ${details}` : reason;

  const formData = new FormData();
  formData.append('action', 'flag_post');
  formData.append('post_id', postId);
  formData.append('flag_reason', fullReason);

  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    credentials: 'include',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('Post flagged successfully');
      closeFlagModal2();
      loadWatchPosts();
      loadFlags();
      loadDashboardStats();
    } else {
      alert('Error: ' + (data.message || 'Failed to flag post'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to flag post');
  });
}


function openWarnModal2(userId, itemName) {
  const modal = document.getElementById('warn-modal2');
  if (modal) {
    modal.classList.add('active');
    modal.setAttribute('data-user-id', userId);
    document.getElementById('warn-modal2-post-name').textContent = itemName;
  }
}

function closeWarnModal2() {
  const modal = document.getElementById('warn-modal2');
  const form = document.getElementById('warn-form2');
  if (modal) {
    modal.classList.remove('active');
    modal.removeAttribute('data-user-id');
  }
  if (form) form.reset();
}

function submitWarn2() {
  const modal = document.getElementById('warn-modal2');
  const userId = modal.getAttribute('data-user-id');
  const reason = document.getElementById('warn-reason2').value;
  const details = document.getElementById('warn-details2').value;

  if (!reason) {
    alert('Please select a reason');
    return;
  }

  const fullReason = details ? `${reason}: ${details}` : reason;

  const formData = new FormData();
  formData.append('action', 'issue_warning');
  formData.append('user_id', userId);
  formData.append('reason', fullReason);

  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    credentials: 'include',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('Warning issued successfully');
      closeWarnModal2();
      loadWarnings();
      loadDashboardStats();
    } else {
      alert('Error: ' + (data.message || 'Failed to issue warning'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to issue warning');
  });
}


function openFlagWarnModal(postId, userId, itemName) {
  const modal = document.getElementById('flag-warn-modal');
  if (modal) {
    modal.classList.add('active');
    modal.setAttribute('data-post-id', postId);
    modal.setAttribute('data-user-id', userId);
    document.getElementById('flag-warn-post-name').textContent = itemName;
  }
}

function closeFlagWarnModal() {
  const modal = document.getElementById('flag-warn-modal');
  const form = document.getElementById('flag-warn-form');
  if (modal) {
    modal.classList.remove('active');
    modal.removeAttribute('data-post-id');
    modal.removeAttribute('data-user-id');
  }
  if (form) form.reset();
}

function submitFlagAndWarn() {
  const modal = document.getElementById('flag-warn-modal');
  const postId = modal.getAttribute('data-post-id');
  const userId = modal.getAttribute('data-user-id');
  const reason = document.getElementById('flag-warn-reason').value;
  const details = document.getElementById('flag-warn-details').value;

  if (!reason) {
    alert('Please select a reason');
    return;
  }

  const fullReason = details ? `${reason}: ${details}` : reason;

  
  const flagFormData = new FormData();
  flagFormData.append('action', 'flag_post');
  flagFormData.append('post_id', postId);
  flagFormData.append('flag_reason', fullReason);

  
  const warnFormData = new FormData();
  warnFormData.append('action', 'issue_warning');
  warnFormData.append('user_id', userId);
  warnFormData.append('reason', fullReason);

  
  Promise.all([
    fetch('../Controller/moderator_api.php', {
      method: 'POST',
      credentials: 'include',
      body: flagFormData
    }).then(r => r.json()),
    fetch('../Controller/moderator_api.php', {
      method: 'POST',
      credentials: 'include',
      body: warnFormData
    }).then(r => r.json())
  ])
  .then(([flagResult, warnResult]) => {
    if (flagResult.success && warnResult.success) {
      alert('Post flagged and warning issued successfully');
      closeFlagWarnModal();
      loadWatchPosts();
      loadFlags();
      loadWarnings();
      loadDashboardStats();
    } else {
      let errorMsg = '';
      if (!flagResult.success) errorMsg += 'Flag: ' + (flagResult.message || 'Failed') + '\n';
      if (!warnResult.success) errorMsg += 'Warning: ' + (warnResult.message || 'Failed');
      alert('Error: ' + errorMsg);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to flag and warn');
  });
}

function getStatusClass(status) {
  switch (status) {
    case 'approved': return 'badge-success';
    case 'pending': return 'badge-warning';
    case 'rejected': return 'badge-danger';
    case 'claimed': return 'badge-info';
    default: return 'badge-secondary';
  }
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
  } catch {
    return dateString;
  }
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function escapeJs(str) {
  if (!str) return '';
  return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

function handleWatchPostsFilter() {
  const filterValue = document.getElementById('watchposts-filter').value;
  const filters = {};
  
  switch (filterValue) {
    case 'pending':
      filters.status = 'pending';
      break;
    case 'approved':
      filters.status = 'approved';
      break;
    case 'flagged':
      filters.flagged = true;
      break;
    case 'lost':
      filters.type = 'lost';
      break;
    case 'found':
      filters.type = 'found';
      break;
    default:
      
      break;
  }
  
  loadWatchPosts(filters);
}

function flagPost(postId, reason = 'Flagged by moderator') {
  if (!confirm('Are you sure you want to flag this post?')) return;
  
  const formData = new FormData();
  formData.append('action', 'flag_post');
  formData.append('post_id', postId);
  formData.append('flag_reason', reason);
  
  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    credentials: 'include',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Post flagged successfully');
      loadWatchPosts();
      loadFlags();
      loadDashboardStats();
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to flag post');
  });
}

function unflagPost(postId) {
  if (!confirm('Are you sure you want to remove the flag from this post?')) return;
  
  const formData = new FormData();
  formData.append('action', 'unflag_post');
  formData.append('post_id', postId);
  
  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    credentials: 'include',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Post unflagged successfully');
      loadWatchPosts();
      loadFlags();
      loadDashboardStats();
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to unflag post');
  });
}

function viewPostDetails(postId) {
  
  alert('View post details for ID: ' + postId);
  
}
