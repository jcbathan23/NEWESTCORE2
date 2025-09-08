<?php
require_once 'auth.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$isAdmin = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Submissions | CORE II</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-shield-check"></i> Admin - User Submissions</h1>
            <div>
                <a href="rate-tariff.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Rate & Tariff
                </a>
                <a href="admin.php" class="btn btn-primary">
                    <i class="bi bi-speedometer2"></i> Admin Dashboard
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-warning">Pending Review</h5>
                        <h2 class="text-warning" id="pendingCount">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success">Approved</h5>
                        <h2 class="text-success" id="approvedCount">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-danger">Rejected</h5>
                        <h2 class="text-danger" id="rejectedCount">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info">Total</h5>
                        <h2 class="text-info" id="totalCount">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="row mb-3">
            <div class="col-md-4">
                <select id="statusFilter" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Pending Review">Pending Review</option>
                    <option value="Under Review">Under Review</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <div class="col-md-4">
                <select id="categoryFilter" class="form-select">
                    <option value="">All Categories</option>
                    <option value="Transport">Transport</option>
                    <option value="Logistics">Logistics</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Security">Security</option>
                    <option value="Technology">Technology</option>
                </select>
            </div>
            <div class="col-md-4">
                <button onclick="refreshSubmissions()" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
                <button onclick="bulkApprove()" class="btn btn-success">
                    <i class="bi bi-check-square"></i> Bulk Approve
                </button>
            </div>
        </div>

        <!-- Submissions Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Base Rate</th>
                                <th>Submitted By</th>
                                <th>Status</th>
                                <th>Submitted Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="submissionsTableBody">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Submission Detail Modal -->
    <div class="modal fade" id="submissionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="submissionModalTitle">Submission Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="submissionModalBody">
                    <!-- Details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" onclick="approveFromModal()" id="approveFromModalBtn">
                        <i class="bi bi-check-circle"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger" onclick="rejectFromModal()" id="rejectFromModalBtn">
                        <i class="bi bi-x-circle"></i> Reject
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let allSubmissions = [];
        let selectedSubmissionId = null;
        let selectedSubmissions = [];

        // Load submissions on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSubmissions();
            
            // Add filter event listeners
            document.getElementById('statusFilter').addEventListener('change', filterSubmissions);
            document.getElementById('categoryFilter').addEventListener('change', filterSubmissions);
        });

        async function loadSubmissions() {
            try {
                const response = await fetch('api/user-tariff-submissions.php');
                const submissions = await response.json();
                
                if (response.ok && Array.isArray(submissions)) {
                    allSubmissions = submissions;
                    displaySubmissions(submissions);
                    updateSummaryCards(submissions);
                } else {
                    showNotification('Failed to load submissions', 'danger');
                }
            } catch (error) {
                console.error('Error loading submissions:', error);
                showNotification('Error loading submissions', 'danger');
            }
        }

        function displaySubmissions(submissions) {
            const tbody = document.getElementById('submissionsTableBody');
            tbody.innerHTML = '';
            
            submissions.forEach(submission => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <input type="checkbox" class="submission-checkbox" value="${submission.id}" 
                               onchange="updateSelectedSubmissions()">
                    </td>
                    <td>S${submission.id}</td>
                    <td>${submission.name}</td>
                    <td><span class="badge bg-info">${submission.category}</span></td>
                    <td>₱${parseFloat(submission.base_rate || 0).toFixed(2)}</td>
                    <td>${submission.submitted_by_username || 'Unknown'}</td>
                    <td><span class="badge ${getStatusBadgeClass(submission.status)}">${submission.status}</span></td>
                    <td>${new Date(submission.created_at).toLocaleDateString()}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-info" onclick="viewSubmission(${submission.id})" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${submission.status === 'Pending Review' ? `
                                <button class="btn btn-outline-success" onclick="approveSubmission(${submission.id})" title="Approve">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="rejectSubmission(${submission.id})" title="Reject">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            ` : ''}
                            <button class="btn btn-outline-danger" onclick="deleteSubmission(${submission.id})" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function updateSummaryCards(submissions) {
            const pending = submissions.filter(s => s.status === 'Pending Review').length;
            const approved = submissions.filter(s => s.status === 'Approved').length;
            const rejected = submissions.filter(s => s.status === 'Rejected').length;
            
            document.getElementById('pendingCount').textContent = pending;
            document.getElementById('approvedCount').textContent = approved;
            document.getElementById('rejectedCount').textContent = rejected;
            document.getElementById('totalCount').textContent = submissions.length;
        }

        function getStatusBadgeClass(status) {
            switch(status) {
                case 'Pending Review': return 'bg-warning text-dark';
                case 'Under Review': return 'bg-info';
                case 'Approved': return 'bg-success';
                case 'Rejected': return 'bg-danger';
                default: return 'bg-secondary';
            }
        }

        function filterSubmissions() {
            const statusFilter = document.getElementById('statusFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;
            
            let filtered = allSubmissions;
            
            if (statusFilter) {
                filtered = filtered.filter(s => s.status === statusFilter);
            }
            
            if (categoryFilter) {
                filtered = filtered.filter(s => s.category === categoryFilter);
            }
            
            displaySubmissions(filtered);
        }

        async function viewSubmission(id) {
            try {
                const response = await fetch(`api/user-tariff-submissions.php?id=${id}`);
                const submission = await response.json();
                
                if (response.ok) {
                    selectedSubmissionId = id;
                    document.getElementById('submissionModalTitle').textContent = submission.name;
                    
                    const modalBody = document.getElementById('submissionModalBody');
                    modalBody.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ID:</strong> S${submission.id}</p>
                                <p><strong>Category:</strong> <span class="badge bg-info">${submission.category}</span></p>
                                <p><strong>Base Rate:</strong> ₱${parseFloat(submission.base_rate).toFixed(2)}</p>
                                <p><strong>Per KM Rate:</strong> ₱${parseFloat(submission.per_km_rate || 0).toFixed(2)}</p>
                                <p><strong>Per Hour Rate:</strong> ₱${parseFloat(submission.per_hour_rate || 0).toFixed(2)}</p>
                                <p><strong>Priority Multiplier:</strong> ${parseFloat(submission.priority_multiplier || 1).toFixed(1)}x</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Status:</strong> <span class="badge ${getStatusBadgeClass(submission.status)}">${submission.status}</span></p>
                                <p><strong>Service Area:</strong> ${submission.service_area || 'Not specified'}</p>
                                <p><strong>Submitted By:</strong> ${submission.submitted_by_username}</p>
                                <p><strong>Submitted Date:</strong> ${new Date(submission.created_at).toLocaleString()}</p>
                                ${submission.reviewed_by_user_id ? `<p><strong>Reviewed By:</strong> Admin</p>` : ''}
                                ${submission.reviewed_at ? `<p><strong>Reviewed Date:</strong> ${new Date(submission.reviewed_at).toLocaleString()}</p>` : ''}
                            </div>
                        </div>
                        <hr>
                        <div>
                            <h6>Justification:</h6>
                            <p>${submission.justification}</p>
                        </div>
                        ${submission.notes ? `
                            <div>
                                <h6>Additional Notes:</h6>
                                <p>${submission.notes}</p>
                            </div>
                        ` : ''}
                        ${submission.review_notes ? `
                            <div class="mt-3">
                                <h6>Review Notes:</h6>
                                <div class="alert alert-info">${submission.review_notes}</div>
                            </div>
                        ` : ''}
                    `;
                    
                    // Show/hide action buttons based on status
                    const isPending = submission.status === 'Pending Review';
                    document.getElementById('approveFromModalBtn').style.display = isPending ? 'inline-block' : 'none';
                    document.getElementById('rejectFromModalBtn').style.display = isPending ? 'inline-block' : 'none';
                    
                    new bootstrap.Modal(document.getElementById('submissionModal')).show();
                } else {
                    showNotification('Failed to load submission details', 'danger');
                }
            } catch (error) {
                console.error('Error loading submission:', error);
                showNotification('Error loading submission details', 'danger');
            }
        }

        async function approveSubmission(id) {
            if (await confirmAction('Approve this submission?', 'This will create an official tariff.', 'Yes, Approve')) {
                await performApproval(id);
            }
        }

        async function approveFromModal() {
            if (selectedSubmissionId && await confirmAction('Approve this submission?', 'This will create an official tariff.', 'Yes, Approve')) {
                await performApproval(selectedSubmissionId);
                bootstrap.Modal.getInstance(document.getElementById('submissionModal')).hide();
            }
        }

        async function performApproval(id) {
            try {
                const response = await fetch(`api/user-tariff-submissions.php?id=${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        statusOnly: true,
                        status: 'Approved',
                        reviewNotes: 'Approved by admin'
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    if (result.new_tariff_id) {
                        showNotification(`Submission approved successfully! New tariff created (ID: ${result.new_tariff_id})`, 'success');
                    } else {
                        showNotification('Submission approved successfully!', 'success');
                    }
                    loadSubmissions(); // Refresh the list
                } else {
                    showNotification(result.error || 'Failed to approve submission', 'danger');
                }
            } catch (error) {
                console.error('Error approving submission:', error);
                showNotification('Error approving submission', 'danger');
            }
        }

        async function rejectSubmission(id) {
            const { value: reason } = await Swal.fire({
                title: 'Reject Submission',
                input: 'textarea',
                inputLabel: 'Reason for rejection (optional)',
                inputPlaceholder: 'Please provide a reason...',
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#dc3545'
            });

            if (reason !== undefined) {
                await performRejection(id, reason);
            }
        }

        async function rejectFromModal() {
            const { value: reason } = await Swal.fire({
                title: 'Reject Submission',
                input: 'textarea',
                inputLabel: 'Reason for rejection (optional)',
                inputPlaceholder: 'Please provide a reason...',
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#dc3545'
            });

            if (reason !== undefined && selectedSubmissionId) {
                await performRejection(selectedSubmissionId, reason);
                bootstrap.Modal.getInstance(document.getElementById('submissionModal')).hide();
            }
        }

        async function performRejection(id, reason) {
            try {
                const response = await fetch(`api/user-tariff-submissions.php?id=${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        statusOnly: true,
                        status: 'Rejected',
                        reviewNotes: reason || 'Rejected by admin'
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    showNotification('Submission rejected successfully', 'info');
                    loadSubmissions(); // Refresh the list
                } else {
                    showNotification(result.error || 'Failed to reject submission', 'danger');
                }
            } catch (error) {
                console.error('Error rejecting submission:', error);
                showNotification('Error rejecting submission', 'danger');
            }
        }

        async function deleteSubmission(id) {
            if (await confirmAction('Delete this submission?', 'This action cannot be undone.', 'Yes, Delete', 'danger')) {
                try {
                    const response = await fetch(`api/user-tariff-submissions.php?id=${id}`, {
                        method: 'DELETE'
                    });

                    if (response.ok) {
                        showNotification('Submission deleted successfully', 'info');
                        loadSubmissions(); // Refresh the list
                    } else {
                        const result = await response.json();
                        showNotification(result.error || 'Failed to delete submission', 'danger');
                    }
                } catch (error) {
                    console.error('Error deleting submission:', error);
                    showNotification('Error deleting submission', 'danger');
                }
            }
        }

        function refreshSubmissions() {
            loadSubmissions();
            showNotification('Submissions refreshed', 'info');
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.submission-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedSubmissions();
        }

        function updateSelectedSubmissions() {
            const checkboxes = document.querySelectorAll('.submission-checkbox:checked');
            selectedSubmissions = Array.from(checkboxes).map(cb => parseInt(cb.value));
        }

        async function bulkApprove() {
            if (selectedSubmissions.length === 0) {
                showNotification('Please select submissions to approve', 'warning');
                return;
            }

            if (await confirmAction(`Approve ${selectedSubmissions.length} submissions?`, 'This will create official tariffs.', 'Yes, Approve All')) {
                let approved = 0;
                let errors = 0;

                for (const id of selectedSubmissions) {
                    try {
                        const response = await fetch(`api/user-tariff-submissions.php?id=${id}`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                statusOnly: true,
                                status: 'Approved',
                                reviewNotes: 'Bulk approved by admin'
                            })
                        });

                        if (response.ok) {
                            approved++;
                        } else {
                            errors++;
                        }
                    } catch (error) {
                        errors++;
                    }
                }

                showNotification(`Bulk approval completed: ${approved} approved, ${errors} errors`, 
                                 errors === 0 ? 'success' : 'warning');
                loadSubmissions(); // Refresh the list
                
                // Clear selections
                document.getElementById('selectAll').checked = false;
                selectedSubmissions = [];
            }
        }

        async function confirmAction(title, text, confirmText, type = 'warning') {
            const result = await Swal.fire({
                title: title,
                text: text,
                icon: type,
                showCancelButton: true,
                confirmButtonText: confirmText,
                confirmButtonColor: type === 'danger' ? '#dc3545' : '#198754'
            });
            return result.isConfirmed;
        }

        function showNotification(message, type = 'info') {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alert);
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>
