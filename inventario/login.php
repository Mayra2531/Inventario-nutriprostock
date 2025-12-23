<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriProStock - Sistema de Inventario</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fdf2f8 0%, #f5e6ff 50%, #fce7f3 100%);
            position: relative;
            overflow: hidden;
        }

        /* Elementos decorativos de fondo */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(237, 23, 145, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(69, 35, 73, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .login-container {
            display: flex;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(69, 35, 73, 0.15);
            overflow: hidden;
            max-width: 900px;
            width: 90%;
            position: relative;
            z-index: 1;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #ed1791 0%, #452349 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
        }

        .login-left .brand-icon {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .login-left .brand-icon i {
            font-size: 50px;
            color: white;
        }

        .login-left h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-align: center;
        }

        .login-left .subtitle {
            font-size: 1.1rem;
            font-weight: 300;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .login-left p {
            font-size: 1rem;
            opacity: 0.9;
            text-align: center;
            max-width: 280px;
            line-height: 1.6;
        }

        .login-left .features {
            margin-top: 40px;
            text-align: left;
        }

        .login-left .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .login-left .feature-item i {
            margin-right: 12px;
            background: rgba(255,255,255,0.2);
            padding: 8px;
            border-radius: 8px;
        }

        .login-right {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-right h2 {
            color: #452349;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .login-right .subtitle {
            color: #888;
            font-size: 0.95rem;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #452349;
            margin-bottom: 8px;
        }

        .form-group .input-wrapper {
            position: relative;
        }

        .form-group .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #ed1791;
            font-size: 1rem;
        }

        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #f0e6f5;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #fdf8fc;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ed1791;
            background: white;
            box-shadow: 0 0 0 4px rgba(237, 23, 145, 0.1);
        }

        .form-group input::placeholder {
            color: #aaa;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: #666;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            accent-color: #ed1791;
        }

        .forgot-password {
            font-size: 0.85rem;
            color: #ed1791;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #452349;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ed1791 0%, #c41076 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(237, 23, 145, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(237, 23, 145, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.9rem;
            color: #888;
        }

        .login-footer a {
            color: #ed1791;
            text-decoration: none;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 450px;
            }

            .login-left {
                padding: 40px 30px;
            }

            .login-left h1 {
                font-size: 2rem;
            }

            .login-left .features {
                display: none;
            }

            .login-right {
                padding: 40px 30px;
            }
        }

        /* Animación de entrada */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Mensaje de error */
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .alert-error {
            background: #ffe6e6;
            color: #d63031;
            border: 1px solid #ffcccc;
        }

        .alert i {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="brand-icon">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <h1>NutriProStock</h1>
            <span class="subtitle">Sistema de Inventario</span>
            <p>Gestiona tu inventario de productos, suplementos y materiales de consulta</p>
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-cubes"></i>
                    <span>Control de productos</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-arrow-trend-up"></i>
                    <span>Entradas y salidas</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-bell"></i>
                    <span>Alertas de stock bajo</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-file-invoice"></i>
                    <span>Reportes detallados</span>
                </div>
            </div>
        </div>
        <div class="login-right">
            <h2>Bienvenida 👋</h2>
            <p class="subtitle">Ingresa tus credenciales para acceder al sistema</p>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                Usuario o contraseña incorrectos
            </div>
            <?php endif; ?>

            <form action="validar.php" method="POST">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                    </div>
                </div>
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="recordar">
                        Recordarme
                    </label>
                    <a href="#" class="forgot-password">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
            <div class="login-footer">
                ¿Necesitas ayuda? <a href="#">Contactar soporte</a>
            </div>
        </div>
    </div>
</body>
</html>
