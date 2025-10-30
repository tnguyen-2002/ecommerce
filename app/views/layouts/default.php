<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $this->e($title) ?></title>

    <!-- Styles -->
    <link href=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="/css/style.css" rel="stylesheet">

    <?= $this->section("page_specific_css") ?>
</head>

<body>
    <nav class="navbar navbar-expand-md sticky-top navbar-light bg-light">
        <div class="container-fluid">
            <!-- Branding Image -->
            <a class="navbar-brand" href="/">
                <?= $this->e($title) ?>
            </a>

            <!-- Collapsed Hamburger -->
            <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#app-navbar-collapse">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="app-navbar-collapse">
                <!-- Left Side Of Navbar -->
                <div class="navbar-nav">
                    &nbsp;
                </div>

                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ms-auto">
                    <!-- Authentication Links -->
                    <?php if (!AUTHGUARD()->isUserLoggedIn()) : ?>
                        <li class="nav-item"><a class="nav-link" href="/login">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="/register">Register</a></li>
                    <?php else : ?>
                        <li class="nav-item dropstart">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <?= $this->e(AUTHGUARD()->user()->name) ?> <span class="caret"></span>
                            </a>

                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Logout
                                </a>
                                <form id="logout-form" class="d-none" action="/logout" method="POST">
                                </form>
                            </div>
                        </li>
                    <?php endif ?>
                </ul>
            </div>
        </div>
    </nav>

    <?= $this->section("page") ?>

    <footer class="footer">
        <div class="container text-center">
            <p class="text-muted">Copyright &copy; 2025 Web Development Course</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <?= $this->section("page_specific_js") ?>
</body>

</html>