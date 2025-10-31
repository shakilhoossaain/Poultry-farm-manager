<?php

if (!defined('ABSPATH')) exit;

$crud = new Poultry_CRUD();

if (isset($_POST['pfm_save_project'])) {
    $data = [
        'project_name' => sanitize_text_field($_POST['project_name']),
        'start_date' => sanitize_text_field($_POST['start_date']),
        'initial_chickens' => intval($_POST['initial_chickens']),
        'starting_age_days' => intval($_POST['starting_age_days']),
        'stock_eggs' => intval($_POST['stock_eggs']),
        'notes' => sanitize_textarea_field($_POST['notes']),
        'status' => 'running'
    ];

    if (!empty($_POST['project_id'])) {
        // Edit project
        $project_id = intval($_POST['project_id']);
        $crud->edit_project(intval($_POST['project_id']), $data);
        // echo '<div class="updated"><p>Project updated.</p></div>';
        $msg = 'updated';
    } else {
        // Add project
        // $crud->add_project($data);
        // echo '<div class="updated"><p>Project added.</p></div>';
        $project_id = $crud->add_project($data);
        $msg = 'added';
    }

    $redirect_url = add_query_arg(
        [
            'project_id' => $project_id,
            'message'    => $msg
        ],
        admin_url('admin.php?page=poultry-farm')
    );

    wp_safe_redirect($redirect_url);
    exit;
}


// Show message after redirect
if (!empty($_GET['message'])) {
    if ($_GET['message'] === 'added') {
        echo '<div class="updated"><p>Project updated.</p></div>';
    } elseif ($_GET['message'] === 'updated') {
        echo '<div class="updated"><p>Project added.</p></div>';
    }
}


$projects = $crud->get_all_projects();

?>
<div class="wrap">
    <h1>Projects</h1>
    <div class="pfm-container">
        <div class="pfm-box">
            <h2 id="form-title">Add New Project</h2>
            <form method="post" id="project-form">
                <input type="hidden" name="project_id" id="project_id">
                <table class="form-table">
                    <tr>
                        <th>Project Name</th>
                        <td><input type="text" name="project_name" id="project_name" required></td>
                    </tr>
                    <tr>
                        <th>Start Date</th>
                        <td><input type="date" name="start_date" id="start_date" required></td>
                    </tr>
                    <tr>
                        <th>Initial Chickens</th>
                        <td><input type="number" name="initial_chickens" id="initial_chickens" required></td>
                    </tr>
                    <tr>
                        <th>Starting Age</th>
                        <td><input type="number" name="starting_age_days" id="starting_age_days" required></td>
                    </tr>
                    <tr>
                        <th>Stock Eggs</th>
                        <td><input type="number" name="stock_eggs" id="stock_eggs"></td>
                    </tr>
                    <tr>
                        <th>Notes</th>
                        <td><textarea name="notes" id="notes"></textarea></td>
                    </tr>
                </table>
                <p><input type="submit" name="pfm_save_project" id="save-btn" class="button-primary" value="Add Project"></p>
            </form>
        </div>
        <div class="pfm-box">
            <div class="filterr">
                <select id="project-filter">
                    <option value="all">All Projects</option>
                    <option value="running">Running Projects</option>
                    <option value="completed">Completed Projects</option>
                </select>
            </div>
            <table id="projects-table" class="widefat striped">
                <thead>
                    <tr>
                        <th class='for-now'>ID</th>
                        <th>Name</th>
                        <th>Start Date</th>
                        <th>Chickens</th>
                        <th>Eggs Stock</th>
                        <th>Status</th>
                        <th class="action-head">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($projects) {
                        foreach ($projects as $p) {
                            $json = esc_attr(json_encode($p));
                            $conplate_edit_btn = ($p->status !== 'completed') ? "<button class='button complate-project' data-id='{$p->id}'>Complate</button>
                    <button class='button edit-project' data-id='{$p->id}'>Edit</button>" : '';
                            $datee = date('d F Y', strtotime($p->start_date));
                            echo "<tr data-project='{$json}'>
                <td class='for-now'>{$p->id}</td>
                <td>{$p->project_name}</td>
                <td>{$datee}</td>
                <td>{$p->initial_chickens}</td>
                <td>{$p->stock_eggs}</td>
                <td>{$p->status}</td>
                <td class='pfm-actions'>
                    <button class='button view-project' data-id='{$p->id}'>View</button>
                        {$conplate_edit_btn}
                    <button class='button delete-project' data-id='{$p->id}'>Delete</button>
                </td>
            </tr>";
                        }
                    } else {
                        echo "<tr class='no-projects'><td colspan='6'>No projects found.</td></tr>";
                    } ?>
                    <tr class="no-projects" style="display:none;">
                        <td colspan="6">No projects found</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div id="log-popup" style="display:none;">
        <div class="log-popup-overlay"></div>
        <div class="log-popup-content">
            <span class="log-popup-close">&times;</span>
            <h2>Project Details</h2>
            <div class="log-popup-body"></div>
        </div>
    </div>
</div>




<script>
    jQuery(document).ready(function($) {
        // Edit project - Fill form
        $(document).on('click', '.edit-project', function() {
            const project = $(this).closest('tr').data('project');
            $('#project_id').val(project.id);
            $('#project_name').val(project.project_name);
            $('#start_date').val(project.start_date);
            $('#initial_chickens').val(project.initial_chickens);
            $('#starting_age_days').val(project.starting_age_days);
            $('#stock_eggs').val(project.stock_eggs).parents('tr').hide();
            $('#notes').val(project.notes);



            $('#save-btn').val('Update Project');
            $('#form-title').text('Edit Project');
        });

        //Complate Project
        $(document).on('click', '.complate-project', function() {
            if (!confirm('Mark this project as complete?')) return;

            const projectID = $(this).data('id');
            const row = $(this).closest('tr');

            $.post(ajaxurl, {
                action: 'pfm_complete_project',
                project_id: projectID
            }, function(response) {
                if (response.success) {
                    // Update status column in table
                    row.find('td:nth-child(6)').text('completed');
                    row.find('td:nth-child(7) .complate-project, td:nth-child(7) .edit-project').remove();
                } else {
                    alert('Failed to complete project.');
                }
            });
        });

        // Delete project
        $(document).on('click', '.delete-project', function() {
            if (!confirm('Are you sure you want to delete this project?')) return;
            const projectID = $(this).data('id');
            const row = $(this).closest('tr');

            $.post(ajaxurl, {
                action: 'pfm_delete_project',
                project_id: projectID
            }, function(response) {
                if (response.success) row.remove();
                else alert('Failed to delete project.');
            });
        });

        // View project popup
        $(document).on('click', '.view-project', function() {
            const project = $(this).closest('tr').data('project');
            const startDate = new Date(project.start_date).toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });

            let html = '<table>';
            html += `<tr><td>Project Name</td><td>${project.project_name}</td></tr>`;
            html += `<tr><td>Start Date</td><td>${startDate}</td></tr>`;
            html += `<tr><td>Initial Chickens</td><td>${project.initial_chickens}</td></tr>`;
            html += `<tr><td>Eggs Stock</td><td>${project.stock_eggs}</td></tr>`;
            html += `<tr><td>Starting Age</td><td>${project.starting_age_days}</td></tr>`;
            html += `<tr><td>Status</td><td>${project.status}</td></tr>`;
            html += `<tr><td>Notes</td><td>${project.notes}</td></tr>`;
            html += '</table>';

            $('#log-popup .log-popup-body').html(html);
            $('#log-popup').addClass('active');
        });

        // Close popup
        $(document).on('click', '.log-popup-close, .log-popup-overlay', function() {
            $('#log-popup').removeClass('active');
        });

        // Filter projects using select
        $('#project-filter').on('change', function() {
            const filter = $(this).val();
            let visibleCount = 0;

            $('#projects-table tbody tr').each(function() {
                const status = $(this).find('td:nth-child(6)').text().toLowerCase();
                if ($(this).hasClass('no-projects')) return;

                if (filter === 'all' || status === filter) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });
            if (visibleCount === 0) {
                $('#projects-table tbody .no-projects').show();
            } else {
                $('#projects-table tbody .no-projects').hide();
            }
        });
    });
</script>