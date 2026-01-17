// Initialize dashboard
document.addEventListener('DOMContentLoaded', function () {
  initializeDashboard();
  loadData();
});

function initializeDashboard() {
  // Navigation
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      const sectionId = item.getAttribute('data-section');
      switchSection(sectionId);
    });
  });

  // Filter listeners
  document.getElementById('flag-filter')?.addEventListener('change', () => {
    filterFlags(document.getElementById('flag-filter').value);
  });

  document.getElementById('dispute-filter')?.addEventListener('change', () => {
    filterDisputes(document.getElementById('dispute-filter').value);
  });

  // Modal click outside
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
  // Hide all sections
  const sections = document.querySelectorAll('.content-section');
  sections.forEach(section => section.classList.remove('active'));

  // Show selected section
  document.getElementById(sectionId).classList.add('active');

  // Update nav items
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => item.classList.remove('active'));
  document.querySelector(`[data-section="${sectionId}"]`).classList.add('active');
}

function loadData() {
  loadDashboardStats();
  loadWarnings();
  loadFlags();
  loadDisputes();
  loadModeratorProfile();
}

// ============ DASHBOARD ============
function loadDashboardStats() {
  fetch('../Controller/moderator_api.php?action=get_dashboard_stats')
    .then(response => response.json())
    .then(data => {
      document.getElementById('total-warnings').textContent = data.total_warnings || 0;
      document.getElementById('total-flagged').textContent = data.total_flagged || 0;
      document.getElementById('total-disputes').textContent = data.total_disputes || 0;
      document.getElementById('pending-actions').textContent = data.pending_actions || 0;
    })
    .catch(error => console.error('Error loading stats:', error));
}

// ============ WARN STUDENTS ============
function loadWarnings() {
  fetch('../Controller/moderator_api.php?action=get_warnings')
    .then(response => response.json())
    .then(data => {
      displayWarnings(data);
    })
    .catch(error => console.error('Error loading warnings:', error));
}

function displayWarnings(warnings) {
  const tbody = document.getElementById('warnings-list');
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
  // Load available students
  fetch('../Controller/moderator_api.php?action=get_students')
    .then(response => response.json())
    .then(data => {
      const select = document.getElementById('warn-student-id');
      select.innerHTML = '<option value="">Choose a student...</option>';
      if (data && data.length > 0) {
        select.innerHTML += data.map(student => `
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

  const formData = new FormData();
  formData.append('action', 'issue_warning');
  formData.append('student_id', studentId);
  formData.append('reason', reason);
  formData.append('details', details);

  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    body: formData
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
    body: formData
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

// ============ FLAG POSTS ============
function loadFlags() {
  fetch('../Controller/moderator_api.php?action=get_flags')
    .then(response => response.json())
    .then(data => {
      displayFlags(data);
    })
    .catch(error => console.error('Error loading flags:', error));
}

function displayFlags(flags) {
  const tbody = document.getElementById('flags-list');
  if (!flags || flags.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">No flagged posts</td></tr>';
    return;
  }

  tbody.innerHTML = flags.map(flag => `
    <tr>
      <td>${flag.post_id}</td>
      <td>${flag.title}</td>
      <td>${flag.posted_by}</td>
      <td>${flag.flag_reason}</td>
      <td><span class="status-badge status-${flag.status}">${flag.status}</span></td>
      <td>${flag.flagged_date || '-'}</td>
      <td>
        <button class="btn btn-secondary btn-sm" onclick="reviewFlag(${flag.id})">Review</button>
        <button class="btn btn-danger btn-sm" onclick="removePost(${flag.post_id})">Remove Post</button>
      </td>
    </tr>
  `).join('');
}

function filterFlags(status) {
  if (!status) {
    loadFlags();
    return;
  }

  fetch(`../Controller/moderator_api.php?action=get_flags&status=${status}`)
    .then(response => response.json())
    .then(data => displayFlags(data))
    .catch(error => console.error('Error filtering flags:', error));
}

function reviewFlag(flagId) {
  document.getElementById('flag-modal').classList.add('active');
  document.getElementById('flag-modal').setAttribute('data-flag-id', flagId);
}

function closeFlagModal() {
  document.getElementById('flag-modal').classList.remove('active');
  document.getElementById('flag-form').reset();
  document.getElementById('flag-modal').removeAttribute('data-flag-id');
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
    body: formData
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

function removePost(postId) {
  if (!confirm('Are you sure you want to remove this post?')) return;

  const formData = new FormData();
  formData.append('action', 'remove_post');
  formData.append('post_id', postId);

  fetch('../Controller/moderator_api.php', {
    method: 'POST',
    body: formData
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

// ============ SETTLE DISPUTES ============
function loadDisputes() {
  fetch('../Controller/moderator_api.php?action=get_disputes')
    .then(response => response.json())
    .then(data => {
      displayDisputes(data);
    })
    .catch(error => console.error('Error loading disputes:', error));
}

function displayDisputes(disputes) {
  const tbody = document.getElementById('disputes-list');
  if (!disputes || disputes.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">No open disputes</td></tr>';
    return;
  }

  tbody.innerHTML = disputes.map(dispute => `
    <tr>
      <td>${dispute.dispute_id}</td>
      <td>${dispute.complainant_name}</td>
      <td>${dispute.respondent_name}</td>
      <td>${dispute.issue}</td>
      <td><span class="status-badge status-${dispute.status}">${dispute.status}</span></td>
      <td>${dispute.created_date || '-'}</td>
      <td>
        <button class="btn btn-secondary btn-sm" onclick="openDisputeModal(${dispute.dispute_id})">Resolve</button>
      </td>
    </tr>
  `).join('');
}

function filterDisputes(status) {
  if (!status) {
    loadDisputes();
    return;
  }

  fetch(`../Controller/moderator_api.php?action=get_disputes&status=${status}`)
    .then(response => response.json())
    .then(data => displayDisputes(data))
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

// ============ PROFILE ============
function loadModeratorProfile() {
  fetch('../Controller/moderator_api.php?action=get_moderator_profile')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        document.getElementById('profile-name').textContent = data.name || 'Moderator';
        document.getElementById('profile-email').textContent = data.email || 'email@example.com';
        document.getElementById('detail-name').textContent = data.name || '-';
        document.getElementById('detail-email').textContent = data.email || '-';
        document.getElementById('detail-joined').textContent = data.created_at || '-';
        document.getElementById('header-profile-name').textContent = data.name || 'Moderator';

        // Populate edit form
        document.getElementById('edit-name').value = data.name || '';
        document.getElementById('edit-email').value = data.email || '';
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
    body: formData
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

// ============ MODAL FUNCTIONS ============
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
