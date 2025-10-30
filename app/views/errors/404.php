<?php $this->layout("layouts/default", ["title" => APPNAME]) ?>

<?php $this->start("page") ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="card shadow">
                <div class="card-body">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h2 class="text-danger">404 - Page Not Found</h2>
                    <p class="lead">The short URL you're looking for doesn't exist or has been removed.</p>
                    <p class="text-muted">Please check the URL and try again.</p>
                    <a href="/" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Go Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>