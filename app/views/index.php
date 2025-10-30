<?php $this->layout("layouts/default", ["title" => APPNAME]) ?>


<?php $this->start("page_specific_css") ?>
<link href="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.3.2/r-3.0.5/sp-2.3.4/datatables.min.css" rel="stylesheet"
    integrity="sha384-nr2HNI1xgpaxEmc/HsSP/DR+eSV9xHkv8HVNcK9ydju/T9AP5e7WvMyfskuiENc7" crossorigin="anonymous">
<?php $this->stop() ?>

//urls_create form
<?php $this->start("page") ?>
<div class="container mt-4">
    <!-- Popup Messages Section -->
    <?php
    // Get flash data from session
    $flashData = $_SESSION['flash_data'] ?? [];
    $success = $flashData['success'] ?? null;
    $errors = $flashData['errors'] ?? [];
    $old = $flashData['old'] ?? [];
    
    // Clear flash data after reading
    unset($_SESSION['flash_data']);
    ?>

    <!-- Success Popup -->
    <?php if ($success): ?>
    <div class="alert alert-success" id="successPopup">
        <span class="close-btn" onclick="this.parentElement.style.display='none'">&times;</span>
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <!-- Error Popup -->
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" id="errorPopup">
        <span class="close-btn" onclick="this.parentElement.style.display='none'">&times;</span>
        <strong>Error!</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>


    <!-- URL Shortening Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-cut me-2"></i>Shorten Your URL</h4>
                    <?php if (!empty($tags)): ?>
                        <div class="d-flex align-items-center">
                            <span class="me-3 text-muted small">Filter by tags:</span>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($tags as $tag): ?>
                                    <?php $isSelected = in_array($tag->name, $selectedTags); ?>
                                    <div class="btn-group" role="group">
                                        <?php
                                        // Build the new selected tags array.
                                        // If the tag is already selected, remove it from the array.
                                        // If the tag is not selected, add it to the array.
                                        $updatedTagSelection = $isSelected
                                            ? array_filter($selectedTags, fn($t) => $t !== $tag->name)
                                            : [...$selectedTags, $tag->name];

                                        // Build the query parameters
                                        $queryParams['tags'] = $updatedTagSelection;
                                        ?>
                                        <a href="?<?= http_build_query($queryParams) ?>"
                                            class="btn btn-sm <?= $isSelected ? 'btn-primary' : 'btn-outline-primary' ?>">
                                            <?= $this->e($tag->name) ?>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" onclick="updateTag(<?= $tag->id ?>, '<?= $this->e($tag->name) ?>')">
                                                    <i class="fas fa-edit me-2"></i>Edit Tag
                                                </a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li><a class="dropdown-item text-danger" onclick="deleteTag(<?= $tag->id ?>, '<?= $this->e($tag->name) ?>')">
                                                    <i class="fas fa-trash me-2"></i>Delete Tag
                                                </a></li>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($selectedTags)): ?>
                                    <?php
                                    $clearFilterParams = $_GET;
                                    unset($clearFilterParams['tags']);
                                    ?>
                                    <a href="?<?= http_build_query($clearFilterParams) ?>"
                                        class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <form action="/shorten" method="POST" id="urlForm">
                        <input type="hidden" name="id" id="urlId">
                        <div class="mb-3">
                            <label for="longUrl" class="form-label">Enter your long URL</label>
                            <input type="url" class="form-control <?= isset($errors['longUrl']) ? 'is-invalid' : '' ?>"
                                id="longUrl" name="longUrl"
                                placeholder="https://example.com/very-long-url-that-needs-shortening"
                                value="<?= $old['longUrl'] ?? '' ?>" required>
                            <?php if (isset($errors['longUrl'])): ?>
                                <div class="invalid-feedback"><?= $this->e($errors['longUrl']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags (comma separated)</label>
                            <input type="text" class="form-control" id="tags" name="tags"
                                placeholder="work, important, personal" value="<?= $old['tags'] ?? '' ?>">
                            <div class="form-text">Optional: Add tags to organize your URLs</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="fas fa-cut me-2"></i>Shorten URL
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg" id="cancelBtn" style="display: none;"
                                onclick="resetFormToCreateMode()">
                                <i class="fas fa-times me-2"></i>Cancel Editing
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- User's URLs List -->
    <?php if (!empty($urls)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-list me-2"></i>Your Shortened URLs</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($selectedTags)): ?>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-filter me-2"></i>
                                Showing <?= count($urls) ?> URL<?= count($urls) !== 1 ? 's' : '' ?> with tags:
                                <?php foreach ($selectedTags as $tagName): ?>
                                    <span class="badge bg-primary me-1"><?= $this->e($tagName) ?></span>
                                <?php endforeach; ?>
                                <?php
                                $clearParams = $_GET;
                                unset($clearParams['tags']);
                                ?>
                                <a href="?<?= http_build_query($clearParams) ?>" class="btn btn-sm btn-outline-info ms-2">Clear
                                    filters</a>
                            </div>
                        <?php endif; ?>

                        <table id="urlsTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Short URL</th>
                                    <th>Original URL</th>
                                    <th>Tags</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($urls as $url): ?>
                                    <tr>
                                        <td>
                                            <a href="/<?= $this->e($url->shortSlug) ?>" target="_blank"
                                                class="text-decoration-none">
                                                <?= $this->e($url->getFullShortUrl()) ?>
                                            </a>
                                            <button class="btn btn-sm btn-outline-secondary ms-2"
                                                onclick="copyToClipboard(event, '<?= $this->e($url->getFullShortUrl()) ?>')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;"
                                                title="<?= $this->e($url->longUrl) ?>">
                                                <?= $this->e($url->longUrl) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($url->tags)): ?>
                                                <?php foreach ($url->tags as $tag): ?>
                                                    <span class="badge bg-secondary me-1"><?= $this->e($tag->name) ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">No tags</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($url->createdAt)) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick="editUrl(<?= $url->id ?>, '<?= $this->e($url->longUrl) ?>', '<?= $this->e(implode(', ', $url->getTagNames())) ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUrl(<?= $url->id ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row mt-5">
            <div class="col-12 text-center">
                <div class="card shadow">
                    <div class="card-body">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <?php if (!empty($selectedTags)): ?>
                            <h5 class="text-muted">No URLs found with the selected tags.</h5>
                            <p class="text-muted">Try adjusting your filters criteria or <a href="?"
                                    class="text-decoration-none">Clear all filters</a>.</p>
                        <?php else: ?>
                            <h5 class="text-muted">No shortened URLs yet</h5>
                            <p class="text-muted">Start by shortening your first URL above!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Delete URL Modal -->
    <div class="modal fade" id="deleteUrlModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete URL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this URL? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="/delete-url" method="POST" style="display: inline;">
                        <input type="hidden" name="id" id="deleteUrlId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Tag Modal -->
    <div class="modal fade" id="editTagModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="/update-tag" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editTagId">
                        <div class="mb-3">
                            <label for="editTagName" class="form-label">Tag Name</label>
                            <input type="text" class="form-control" id="editTagName" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Tag</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Tag Modal -->
    <div class="modal fade" id="deleteTagModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the tag "<span id="deleteTagName"></span>"?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This will remove this tag from all URLs that use it.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="/delete-tag" method="POST" style="display: inline;">
                        <input type="hidden" name="id" id="deleteTagId">
                        <button type="submit" class="btn btn-danger">Delete Tag</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
<?php $this->stop() ?>

<?php $this->start("page_specific_js") ?>
<script src="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.3.2/r-3.0.5/sp-2.3.4/datatables.min.js"
    integrity="sha384-wfP/If1m8lEtZeg/oZadbMmyCTfHKbV1zUtkIXBivvPKvAXOk6YN4ToytksJCuJz"
    crossorigin="anonymous"></script>
<script>
    let table = new DataTable('#urlsTable', {
        responsive: true,
        pagingType: 'simple_numbers'
    });

    function copyToClipboard(event, text) {
        navigator.clipboard.writeText(text).then(function () {
            // Show a temporary success message
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');

            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 1000);
        });
    }

    // Auto-hide popups after a few seconds
    document.addEventListener('DOMContentLoaded', function() {
        const successPopup = document.getElementById('successPopup');
        if (successPopup) {
            setTimeout(() => {
                successPopup.style.display = 'none';
            }, 3000);
        }
        const errorPopup = document.getElementById('errorPopup');
        if (errorPopup) {
            setTimeout(() => {
                errorPopup.style.display = 'none';
            }, 4000);
        }
    });

    function editUrl(id, longUrl, tags) {
        //Update form for edit mode
        document.getElementById('urlId').value = id;
        document.getElementById('longUrl').value = longUrl;
        document.getElementById('tags').value = tags;

        //Update form action and button text
        const form = document.getElementById('urlForm');
        const submitBtn = document.getElementById('submitBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const cardHeader = document.querySelector('.card-header h4');

        form.action = '/update-url';
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Update URL';
        submitBtn.className = 'btn btn-warning btn-lg';
        cancelBtn.style.display = 'inline-block'; // Show cancel button
        cardHeader.innerHTML = '<i class="fas fa-edit me-2"></i> Edit URL';
        cardHeader.parentElement.className = 'card-header bg-warning text-white';

        // Scroll to form
        document.querySelector('.card').scrollIntoView({
            behavior: 'smooth'
        });
    }

    function resetFormToCreateMode() {
        const form = document.getElementById('urlForm');
        const submitBtn = document.getElementById('submitBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const cardHeader = document.querySelector('.card-header h4');

        form.action = '/shorten';
        form.reset();
        document.getElementById('urlId').value = '';
        cancelBtn.style.display = 'none'; // Hide cancel button

        submitBtn.innerHTML = '<i class="fas fa-cut me-2"></i> Shorten URL';
        submitBtn.className = 'btn btn-primary btn-lg';
        cardHeader.innerHTML = '<i class="fas fa-cut me-2"></i> Shorten Your URL';
        cardHeader.parentElement.className = 'card-header bg-primary text-white';
    }

    function deleteUrl(id) {
        document.getElementById('deleteUrlId').value = id;
        new bootstrap.Modal(document.getElementById('deleteUrlModal')).show();
    }

    function updateTag(id, name) {
        document.getElementById('editTagId').value = id;
        document.getElementById('editTagName').value = name;
        new bootstrap.Modal(document.getElementById('editTagModal')).show();
    }

    function deleteTag(id, name) {
        document.getElementById('deleteTagId').value = id;
        document.getElementById('deleteTagName').innerText = name;
        new bootstrap.Modal(document.getElementById('deleteTagModal')).show();
    }
</script>
<?php $this->stop() ?>