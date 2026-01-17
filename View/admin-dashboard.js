// Initialize dashboard
document.addEventListener('DOMContentLoaded', function () {
  initializeDashboard();
  loadData();
});

// Initialize dashboard navigation
function initializeDashboard() {
  const navItems = document.querySelectorAll('.nav-item');
  
  navItems.forEach(item => {
    item.addEventListener('click', function (e) {
      e.preventDefault();
      
      // Remove active class from all items
      navItems.forEach(nav => nav.classList.remove('active'));
      
      // Add active class to clicked item
      this.classList.add('active');
      
      // Hide all sections
      document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
      });
      
      // Show selected section
      const section = this.getAttribute('data-section');
      document.getElementById(section).classList.add('active');
    });
  });

  // Filter event listeners
  document.getElementById('student-filter').addEventListener('change', filterStudents);
  document.getElementById('post-filter').addEventListener('change', filterPosts);
}

// Load data from server
function loadData() {
  fetchStudents();
  fetchPosts();
  fetchDatabaseStats();
  loadDashboardStats();
  loadAdminProfile();
}

// Fetch students data
function fetchStudents() {
  fetch('../Controller/admin_api.php?action=get_students')
    .then(response => response.json())
    .then(data => {
      displayStudents(data);
      updateStudentStats(data);
    })
    .catch(error => {
      console.error('Error fetching students:', error);
      displayStudents([]);
    });
}

// Display students in table
function displayStudents(students) {
  const studentsList = document.getElementById('students-list');
  
  if (students.length === 0) {
    studentsList.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999;">No students found</td></tr>';
    return;
  }

  studentsList.innerHTML = students.map(student => `
    <tr>
      <td>#${student.student_id || student.id}</td>
      <td>${student.name}</td>
      <td>${student.email}</td>
      <td>
        <span class="status-badge status-${student.status}">
          ${student.status.charAt(0).toUpperCase() + student.status.slice(1)}
        </span>
      </td>
      <td>
        <span class="role-badge ${student.role === 'moderator' ? 'moderator' : ''}">
          ${student.role === 'moderator' ? 'Moderator' : 'User'}
        </span>
      </td>
      <td>${student.created_at ? student.created_at.split(' ')[0] : 'N/A'}</td>
      <td>
        <div class="action-buttons">
          ${student.status !== 'approved' ? `<button class="btn btn-success" onclick="approveStudent(${student.student_id || student.id})">Approve</button>` : ''}
          ${student.status !== 'banned' ? `<button class="btn btn-danger" onclick="banStudent(${student.student_id || student.id})">Ban</button>` : ''}
          ${student.role !== 'moderator' ? `<button class="btn btn-primary" onclick="appointModerator(${student.student_id || student.id})">Make Mod</button>` : `<button class="btn btn-secondary" onclick="removeModerator(${student.student_id || student.id})">Remove Mod</button>`}
        </div>
      </td>
    </tr>
  `).join('');
}

// Filter students
function filterStudents() {
  const filter = document.getElementById('student-filter').value;
  const url = filter 
    ? `../Controller/admin_api.php?action=get_students&filter=${filter}`
    : '../Controller/admin_api.php?action=get_students';
  
  fetch(url)
    .then(response => response.json())
    .then(data => {
      displayStudents(data);
    })
    .catch(error => {
      console.error('Error filtering students:', error);
      displayStudents([]);
    });
}

// Update student stats
function updateStudentStats(students) {
  document.getElementById('total-students').textContent = students.length;
  document.getElementById('approved-students').textContent = students.filter(s => s.status === 'approved').length;
  document.getElementById('banned-students').textContent = students.filter(s => s.status === 'banned').length;
}

// Approve student
function approveStudent(studentId) {
  openModal('Approve Student', `Are you sure you want to approve student #${studentId}?`, () => {
    fetch(`../Controller/admin_api.php?action=approve_student&id=${studentId}`)
      .then(response => response.json())
      .then(data => {
        alert(data.message || 'Student approved successfully');
        loadData();
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to approve student');
      });
  });
}

// Ban student
function banStudent(studentId) {
  openModal('Ban Student', `Are you sure you want to ban student #${studentId}?`, () => {
    fetch(`../Controller/admin_api.php?action=ban_student&id=${studentId}`)
      .then(response => response.json())
      .then(data => {
        alert(data.message || 'Student banned successfully');
        loadData();
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to ban student');
      });
  });
}

// Appoint moderator
function appointModerator(studentId) {
  openModal('Appoint Moderator', `Make student #${studentId} a moderator?`, () => {
    fetch(`../Controller/admin_api.php?action=appoint_moderator&id=${studentId}`)
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

// Remove moderator
function removeModerator(studentId) {
  openModal('Remove Moderator', `Remove moderator status from student #${studentId}?`, () => {
    fetch(`../Controller/admin_api.php?action=remove_moderator&id=${studentId}`)
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

// Fetch posts data
function fetchPosts() {
  fetch('../Controller/admin_api.php?action=get_posts')
    .then(response => response.json())
    .then(data => {
      displayPosts(data);
      updatePostStats(data);
    })
    .catch(error => {
      console.error('Error fetching posts:', error);
      displayPosts([]);
    });
}

// Display posts in table
function displayPosts(posts) {
  const postsList = document.getElementById('posts-list');

  if (posts.length === 0) {
    postsList.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #999;">No posts found</td></tr>';
    return;
  }

  postsList.innerHTML = posts.map(post => `
    <tr>
      <td>#${post.post_id || post.id}</td>
      <td>${post.title}</td>
      <td>${post.category}</td>
      <td>
        <span style="color: ${post.type === 'lost' ? '#ef4444' : '#149444'};">
          ${post.type === 'lost' ? 'Lost' : 'Found'}
        </span>
      </td>
      <td>${post.student_name || 'N/A'}</td>
      <td>
        <span class="status-badge status-${post.status}">
          ${post.status.charAt(0).toUpperCase() + post.status.slice(1)}
        </span>
      </td>
      <td>${post.created_at ? post.created_at.split(' ')[0] : 'N/A'}</td>
      <td>
        <div class="action-buttons">
          ${post.status !== 'approved' ? `<button class="btn btn-success" onclick="approvePost(${post.post_id || post.id})">Approve</button>` : ''}
          ${post.status !== 'banned' ? `<button class="btn btn-danger" onclick="banPost(${post.post_id || post.id})">Ban</button>` : ''}
          <button class="btn btn-secondary" onclick="viewPostDetails(${post.post_id || post.id})">View</button>
        </div>
      </td>
    </tr>
  `).join('');
}

// Filter posts
function filterPosts() {
  const filter = document.getElementById('post-filter').value;
  const url = filter 
    ? `../Controller/admin_api.php?action=get_posts&filter=${filter}`
    : '../Controller/admin_api.php?action=get_posts';
  
  fetch(url)
    .then(response => response.json())
    .then(data => {
      displayPosts(data);
    })
    .catch(error => {
      console.error('Error filtering posts:', error);
      displayPosts([]);
    });
}

// Update post stats
function updatePostStats(posts) {
  document.getElementById('pending-posts').textContent = posts.filter(p => p.status === 'pending').length;
}

// Approve post
function approvePost(postId) {
  openModal('Approve Post', `Approve post #${postId}?`, () => {
    fetch(`../Controller/admin_api.php?action=approve_post&id=${postId}`)
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

// Ban post
function banPost(postId) {
  openModal('Ban Post', `Ban post #${postId}?`, () => {
    fetch(`../Controller/admin_api.php?action=ban_post&id=${postId}`)
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

// View post details
function viewPostDetails(postId) {
  fetch(`../Controller/admin_api.php?action=get_post&id=${postId}`)
    .then(response => response.json())
    .then(data => {
      const details = `
        <p><strong>Title:</strong> ${data.title}</p>
        <p><strong>Description:</strong> ${data.description || 'N/A'}</p>
        <p><strong>Category:</strong> ${data.category}</p>
        <p><strong>Type:</strong> ${data.type === 'lost' ? 'Lost' : 'Found'}</p>
        <p><strong>Status:</strong> ${data.status}</p>
        <p><strong>Date Posted:</strong> ${data.created_at}</p>
      `;
      openModal('Post Details', details, () => {});
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to load post details');
    });
}

// Load dashboard stats
function loadDashboardStats() {
  fetchDatabaseStats();
}

// Fetch database stats
function fetchDatabaseStats() {
  fetch('../Controller/admin_api.php?action=get_database_stats')
    .then(response => response.json())
    .then(data => {
      document.getElementById('students-count').textContent = data.total_students || '0';
      document.getElementById('posts-count').textContent = data.total_posts || '0';
      
      // Update dashboard stats
      document.getElementById('total-students').textContent = data.total_students || '0';
      document.getElementById('approved-students').textContent = data.approved_students || '0';
      document.getElementById('banned-students').textContent = data.total_students - data.approved_students || '0';
      document.getElementById('pending-posts').textContent = data.pending_posts || '0';
    })
    .catch(error => {
      console.error('Error fetching database stats:', error);
    });
}

// View table data
function viewTable(tableName) {
  const modal = document.getElementById('table-view');
  const title = document.getElementById('view-title');
  const table = document.getElementById('view-table');

  title.textContent = `${tableName.charAt(0).toUpperCase() + tableName.slice(1)} Table`;

  fetch(`../Controller/admin_api.php?action=get_${tableName}`)
    .then(response => response.json())
    .then(data => {
      if (tableName === 'students') {
        const headers = ['ID', 'Name', 'Email', 'Status', 'Role', 'Joined'];
        const rows = data.map(row => [
          row.student_id,
          row.name,
          row.email,
          row.status,
          row.role,
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
      } else if (tableName === 'posts') {
        const headers = ['ID', 'Title', 'Category', 'Type', 'Status', 'Date'];
        const rows = data.map(row => [
          row.post_id,
          row.title,
          row.category,
          row.type,
          row.status,
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
      }

      modal.style.display = 'block';
    })
    .catch(error => {
      console.error('Error fetching table data:', error);
      alert('Failed to load table data');
    });
}

// Close table view
function closeTableView() {
  document.getElementById('table-view').style.display = 'none';
}

// Download CSV
function downloadTableCSV(tableName) {
  fetch(`../Controller/admin_api.php?action=get_${tableName}`)
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
      }

      // Convert to CSV
      let csv = headers.join(',') + '\n';
      csvRows.forEach(row => {
        csv += row.map(cell => `"${cell}"`).join(',') + '\n';
      });

      // Download
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

// Backup database
function backupDatabase() {
  openModal('Backup Database', 'This will create a backup of the entire database. Continue?', () => {
    fetch('../Controller/admin_api.php?action=backup_database')
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

// Test connection
function testConnection() {
  const status = document.getElementById('db-status');
  status.textContent = 'Testing...';
  
  fetch('../Controller/admin_api.php?action=get_database_stats')
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

// Refresh data
function refreshData() {
  loadData();
  alert('Data refreshed successfully!');
}

// Modal functions
function openModal(title, content, callback) {
  const modal = document.getElementById('action-modal');
  const modalTitle = document.getElementById('modal-title');
  const modalBody = document.getElementById('modal-body');
  const modalConfirm = document.getElementById('modal-confirm');

  modalTitle.textContent = title;
  modalBody.innerHTML = `<p>${content}</p>`;

  // Store callback
  modalConfirm.onclick = () => {
    callback();
    closeModal();
  };

  modal.classList.add('active');
}

function closeModal() {
  document.getElementById('action-modal').classList.remove('active');
}

// Close modal when clicking outside
document.addEventListener('click', function (e) {
  const modal = document.getElementById('action-modal');
  if (e.target === modal) {
    closeModal();
  }
});

// ===== PROFILE FUNCTIONS =====

// Load admin profile data
function loadAdminProfile() {
  fetch('../Controller/admin_api.php?action=get_admin_profile')
    .then(response => response.json())
    .then(data => {
      if (data) {
        document.getElementById('profile-name').textContent = data.name || 'Admin';
        document.getElementById('profile-email').textContent = data.email || 'admin@trackback.com';
        document.getElementById('header-profile-name').textContent = data.name || 'Admin';
        
        document.getElementById('detail-name').textContent = data.name || '-';
        document.getElementById('detail-email').textContent = data.email || '-';
        document.getElementById('detail-joined').textContent = data.created_at ? new Date(data.created_at).toLocaleDateString() : '-';
      }
    })
    .catch(error => {
      console.error('Error loading admin profile:', error);
    });
}

// Open profile edit modal
function openProfileEditModal() {
  fetch('../Controller/admin_api.php?action=get_admin_profile')
    .then(response => response.json())
    .then(data => {
      if (data) {
        document.getElementById('edit-name').value = data.name || '';
        document.getElementById('edit-email').value = data.email || '';
        document.getElementById('edit-password').value = '';
        document.getElementById('edit-confirm-password').value = '';
        
        document.getElementById('profile-edit-modal').classList.add('active');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to load profile data');
    });
}

// Close profile modal
function closeProfileModal() {
  document.getElementById('profile-edit-modal').classList.remove('active');
}

// Save profile changes
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

// Close profile modal when clicking outside
document.addEventListener('click', function (e) {
  const modal = document.getElementById('profile-edit-modal');
  if (e.target === modal) {
    closeProfileModal();
  }
});
