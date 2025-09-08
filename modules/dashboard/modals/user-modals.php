<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="editUserForm">
      <div class="modal-header">
        <h5 class="modal-title" id="editUserModalLabel">
          <i class="bi bi-person-gear me-2"></i>Edit User Account
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editUserId">
        
        <div class="mb-3">
          <label for="editUsername" class="form-label">Username</label>
          <input type="text" class="form-control" id="editUsername" required>
        </div>
        
        <div class="mb-3">
          <label for="editEmail" class="form-label">Email Address</label>
          <input type="email" class="form-control" id="editEmail" required>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label for="editRole" class="form-label">Role</label>
              <select class="form-select" id="editRole" required>
                <option value="admin">Administrator</option>
                <option value="user">Regular User</option>
                <option value="provider">Service Provider</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label for="editStatus" class="form-label">Status</label>
              <select class="form-select" id="editStatus" required>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i>Cancel
        </button>
        <button type="submit" class="btn btn-primary" id="editUserSubmitBtn">
          <i class="bi bi-check-circle me-1"></i>Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="addUserForm">
      <div class="modal-header">
        <h5 class="modal-title" id="addUserModalLabel">
          <i class="bi bi-person-plus me-2"></i>Add New User
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="addUsername" class="form-label">Username</label>
          <input type="text" class="form-control" id="addUsername" required placeholder="Enter unique username">
        </div>
        
        <div class="mb-3">
          <label for="addEmail" class="form-label">Email Address</label>
          <input type="email" class="form-control" id="addEmail" required placeholder="user@example.com">
        </div>
        
        <div class="mb-3">
          <label for="addPassword" class="form-label">Password</label>
          <input type="password" class="form-control" id="addPassword" required minlength="6" placeholder="Enter secure password">
          <div class="form-text">Password must be at least 6 characters long</div>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label for="addRole" class="form-label">Role</label>
              <select class="form-select" id="addRole" required>
                <option value="">Select Role</option>
                <option value="admin">Administrator</option>
                <option value="user">Regular User</option>
                <option value="provider">Service Provider</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label for="addStatus" class="form-label">Status</label>
              <select class="form-select" id="addStatus">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i>Cancel
        </button>
        <button type="submit" class="btn btn-primary" id="addUserSubmitBtn">
          <i class="bi bi-person-plus me-1"></i>Create User
        </button>
      </div>
    </form>
  </div>
</div>

<style>
/* Simple Modal Enhancements */
.modal-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom: none;
}

.modal-header .modal-title {
  color: white;
  font-weight: 600;
}

.modal-header .btn-close {
  filter: invert(1);
}

.dark-mode .modal-content {
  background-color: rgba(44, 62, 80, 0.95) !important;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.dark-mode .modal-body {
  color: var(--text-light);
}

.dark-mode .modal-footer {
  border-top-color: rgba(255, 255, 255, 0.1);
}

/* Button loading states */
.btn-loading {
  display: none;
}

.btn.loading {
  pointer-events: none;
}

.btn.loading .btn-loading {
  display: inline-block;
}
</style>
