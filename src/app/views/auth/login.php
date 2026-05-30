<?php
$loginHotel = isset($hotel) && is_array($hotel) ? $hotel : null;
$loginHotelId = $loginHotel['hotel_id'] ?? null;
$loginHotelSlug = $loginHotel['slug'] ?? null;
$loginHotelNombre = $loginHotel['nombre_comercial'] ?? 'Los Cedros';
$loginAction = $login_action ?? url('login/authenticate');
$loginDisabled = !empty($login_disabled);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $title ?? 'Los Cedros - Sistema de Gestión' ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#6B4423">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* ========================================
           VARIABLES CSS
           ======================================== */
        :root {
            --primary-color: #9CA777;
            --primary-dark: #7A8B5C;
            --secondary-color: #8B5A3C;
            --gold: #D4AF37;
            --gold-light: #E5D285;
            --cream: #FFF8E7;
            /* Tonos de verde olivo del logo */
            --olive-green: #9CA777;
            --olive-green-light: #B8C49A;
            --olive-green-dark: #7A8B5C;
            --olive-accent: rgba(156, 167, 119, 0.15);
            --dark-color: #2c2c2c;
            --light-color: #f8f9fa;
            --gray-light: #e9ecef;
            --gray-medium: #6c757d;
            --border-color: #ced4da;
            --shadow-light: rgba(107, 68, 35, 0.08);
            --shadow-medium: rgba(107, 68, 35, 0.15);
            --shadow-strong: rgba(107, 68, 35, 0.25);
            --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            --gradient-gold: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
            --gradient-olive: linear-gradient(135deg, var(--olive-green) 0%, var(--olive-green-light) 100%);
        }

        /* ========================================
           RESET Y BASE
           ======================================== */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        html, body {
            overflow: hidden;
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            position: fixed;
            width: 100%;
        }
        
        body {
            min-height: 100vh;
            min-height: -webkit-fill-available;
            background: linear-gradient(135deg, var(--cream) 0%, #ffffff 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--dark-color);
            position: relative;
        }

        /* ========================================
           CONTENEDOR PRINCIPAL - DESKTOP
           ======================================== */
        .login-wrapper {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 
                0 30px 60px rgba(107, 68, 35, 0.15),
                0 10px 30px rgba(107, 68, 35, 0.1);
            min-height: 650px;
            margin: 20px;
            position: relative;
        }

        /* ========================================
           PANEL IZQUIERDO - LOGO Y BRANDING
           ======================================== */
        .left-panel {
            background: var(--gradient-primary);
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        /* Formas decorativas de fondo */
        .shape-bg {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .shape-1 {
            width: 350px;
            height: 350px;
            top: -150px;
            left: -150px;
            background: rgba(156, 167, 119, 0.12); /* Verde olivo sutil */
            animation: float 8s ease-in-out infinite;
        }

        .shape-2 {
            width: 250px;
            height: 250px;
            bottom: -100px;
            right: -100px;
            background: rgba(255, 255, 255, 0.08);
            animation: float 6s ease-in-out infinite reverse;
        }

        .shape-3 {
            width: 180px;
            height: 180px;
            top: 50%;
            left: 20%;
            transform: translate(-50%, -50%);
            background: rgba(156, 167, 119, 0.1); /* Verde olivo sutil */
            animation: float 10s ease-in-out infinite;
        }

        /* Patrón geométrico sutil */
        .geometric-pattern {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(30deg, var(--olive-green) 12%, transparent 12.5%, transparent 87%, var(--olive-green) 87.5%),
                linear-gradient(150deg, var(--gold) 12%, transparent 12.5%, transparent 87%, var(--gold) 87.5%);
            background-size: 80px 140px;
            opacity: 0.04;
        }

        /* Contenedor del logo */
        .logo-container {
            position: relative;
            z-index: 1;
            text-align: center;
            animation: fadeInUp 0.8s ease-out;
        }

        .logo-wrapper {
            width: 280px;
            height: 280px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 40px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.2),
                inset 0 0 0 1px rgba(212, 175, 55, 0.2);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        /* Efecto de brillo en el logo */
        .logo-wrapper::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(212, 175, 55, 0.4), transparent);
            transform: rotate(45deg);
            animation: shine 4s ease-in-out infinite;
        }

        /* Anillo decorativo alrededor del logo */
        .logo-wrapper::after {
            content: '';
            position: absolute;
            inset: -10px;
            border-radius: 50%;
            border: 2px solid var(--olive-green-light);
            animation: pulse-ring 2s ease-in-out infinite;
        }

        .logo-img {
            width: 200px;
            height: 200px;
            object-fit: contain;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }

        /* Información de la marca */
        .brand-info {
            color: white;
            position: relative;
            z-index: 1;
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -1px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, #ffffff 0%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-subtitle {
            font-size: 18px;
            font-weight: 300;
            opacity: 0.95;
            line-height: 1.6;
            max-width: 400px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        /* Detalles decorativos */
        .decorative-line {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--olive-green) 0%, var(--gold) 50%, var(--olive-green-light) 100%);
            margin: 24px auto;
            border-radius: 3px;
            box-shadow: 0 2px 8px rgba(156, 167, 119, 0.4);
        }

        /* ========================================
           PANEL DERECHO - FORMULARIO
           ======================================== */
        .right-panel {
            padding: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            position: relative;
        }

        /* Decoraciones del panel derecho */
        .corner-decoration {
            position: absolute;
            width: 100px;
            height: 100px;
            border: 3px solid rgba(156, 167, 119, 0.2); /* Verde olivo sutil */
            border-radius: 50%;
            pointer-events: none;
        }

        .corner-decoration.top-left {
            top: -50px;
            left: -50px;
        }

        .corner-decoration.bottom-right {
            bottom: -50px;
            right: -50px;
        }

        .form-container {
            width: 100%;
            max-width: 450px;
            animation: fadeInRight 0.8s ease-out 0.3s both;
            position: relative;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .form-subtitle {
            color: var(--gray-medium);
            font-size: 16px;
            font-weight: 400;
        }

        /* ========================================
           FORMULARIO Y CAMPOS
           ======================================== */
        .login-form {
            margin-top: 40px;
        }

        .form-group {
            margin-bottom: 28px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            color: var(--gray-medium);
            font-size: 18px;
            transition: all 0.3s ease;
            z-index: 1;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px 16px 52px;
            border: 2px solid var(--border-color);
            border-radius: 14px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--light-color);
            color: var(--dark-color);
            font-weight: 500;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--olive-green);
            background: white;
            box-shadow: 0 0 0 4px var(--olive-accent);
        }

        .form-control:focus ~ .input-icon {
            color: var(--olive-green-dark);
            transform: scale(1.1);
        }

        .form-control::placeholder {
            color: var(--gray-medium);
            font-weight: 400;
            opacity: 0.7;
        }

        /* Toggle password button */
        .toggle-password {
            position: absolute;
            right: 18px;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray-medium);
            font-size: 18px;
            transition: all 0.3s ease;
            z-index: 2;
            padding: 4px;
        }

        .toggle-password:hover {
            color: var(--olive-green);
            transform: scale(1.1);
        }

        /* ========================================
           MENSAJES DE ALERTA
           ======================================== */
        .alert-message {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: start;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown 0.4s ease-out;
        }

        .alert-message i {
            font-size: 20px;
            margin-top: 2px;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        /* ========================================
           CHECKBOX Y ENLACES
           ======================================== */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--olive-green);
            margin-right: 8px;
        }

        .remember-me span {
            font-size: 14px;
            color: var(--gray-medium);
            font-weight: 500;
        }

        .forgot-password {
            color: var(--gold);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .forgot-password::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gold);
            transition: width 0.3s ease;
        }

        .forgot-password:hover::after {
            width: 100%;
        }

        .forgot-password:hover {
            color: var(--olive-green-dark);
        }

        /* ========================================
           BOTÓN DE SUBMIT
           ======================================== */
        .submit-btn {
            width: 100%;
            padding: 18px;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            background: var(--gradient-primary);
            color: white;
            box-shadow: 
                0 10px 20px rgba(107, 68, 35, 0.25),
                0 4px 8px rgba(107, 68, 35, 0.15);
        }

        /* Efecto de brillo en el botón */
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 15px 30px rgba(107, 68, 35, 0.35),
                0 6px 12px rgba(107, 68, 35, 0.2);
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* ========================================
           DIVISOR Y FOOTER
           ======================================== */
        .divider {
            margin: 32px 0;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-color), transparent);
        }

        .divider-text {
            color: var(--gray-medium);
            font-size: 14px;
            font-weight: 500;
        }

        /* ========================================
           BADGES DE SEGURIDAD
           ======================================== */
        .security-badges {
            margin-top: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .security-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--gray-medium);
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--light-color) 0%, rgba(156, 167, 119, 0.08) 100%);
            border-radius: 20px;
            border: 1px solid rgba(156, 167, 119, 0.15);
        }

        .security-badge i {
            color: var(--olive-green);
            font-size: 14px;
        }

        /* ========================================
           ANIMACIONES
           ======================================== */
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

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            33% { transform: translateY(-20px) translateX(10px); }
            66% { transform: translateY(10px) translateX(-10px); }
        }

        @keyframes pulse-ring {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.05);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ========================================
           RESPONSIVE - TABLET
           ======================================== */
        @media (max-width: 1024px) {
            .login-wrapper {
                max-width: 900px;
                min-height: 600px;
            }

            .left-panel,
            .right-panel {
                padding: 40px;
            }

            .logo-wrapper {
                width: 240px;
                height: 240px;
            }

            .logo-img {
                width: 170px;
                height: 170px;
            }

            .brand-title {
                font-size: 40px;
            }

            .form-title {
                font-size: 32px;
            }
        }

        /* ========================================
           RESPONSIVE - MOBILE (≤768px)
           ======================================== */
        @media (max-width: 768px) {
            html, body {
                position: static;
                overflow: auto;
                height: auto;
                width: 100%;
            }

            body {
                min-height: 100vh;
                min-height: 100dvh;
                background: white;
                display: block;
            }

            .login-wrapper {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
                margin: 0;
                border-radius: 0;
                min-height: 100vh;
                min-height: 100dvh;
                height: auto;
                max-height: none;
                box-shadow: none;
                overflow: visible;
            }

            /* --- Panel izquierdo: header horizontal compacto --- */
            .left-panel {
                padding: 24px 20px 20px;
                min-height: auto;
                flex-shrink: 0;
                flex-direction: row;
                gap: 16px;
                text-align: left;
                justify-content: flex-start;
                border-radius: 0 0 24px 24px;
            }

            .logo-container {
                display: flex;
                align-items: center;
                gap: 16px;
                flex-direction: row;
            }

            .shape-1, .shape-2, .shape-3 { display: none; }

            .logo-wrapper {
                width: 64px;
                height: 64px;
                margin: 0;
                flex-shrink: 0;
            }

            .logo-wrapper::before,
            .logo-wrapper::after {
                display: none;
            }

            .logo-img {
                width: 46px;
                height: 46px;
            }

            .brand-info {
                text-align: left;
                flex: 1;
                min-width: 0;
            }

            .brand-title {
                font-size: 22px;
                margin-bottom: 2px;
                background: none;
                -webkit-text-fill-color: white;
            }

            .decorative-line { display: none; }

            .brand-subtitle {
                font-size: 12px;
                opacity: 0.85;
                line-height: 1.3;
                max-width: none;
            }

            /* --- Panel derecho: formulario scrolleable --- */
            .right-panel {
                padding: 28px 24px 32px;
                flex: 1;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                align-items: flex-start;
            }

            .corner-decoration { display: none; }

            .form-container {
                max-width: 100%;
                animation: none;
            }

            .form-header {
                margin-bottom: 24px;
            }

            .form-title {
                font-size: 26px;
                margin-bottom: 6px;
            }

            .form-subtitle {
                font-size: 13px;
            }

            .login-form {
                margin-top: 20px;
            }

            .form-group {
                margin-bottom: 18px;
            }

            .form-label {
                margin-bottom: 6px;
                font-size: 12px;
            }

            .form-control {
                padding: 14px 16px 14px 44px;
                font-size: 16px; /* Prevent zoom on iOS */
                border-radius: 12px;
            }

            .input-icon {
                left: 14px;
                font-size: 16px;
            }

            .toggle-password {
                right: 14px;
                font-size: 16px;
            }

            .form-options {
                flex-direction: row;
                flex-wrap: wrap;
                margin-bottom: 20px;
                gap: 8px;
            }

            .remember-me span { font-size: 13px; }
            .forgot-password { font-size: 13px; }

            .submit-btn {
                padding: 15px;
                font-size: 15px;
            }

            .divider {
                margin: 20px 0;
            }

            .security-badges {
                margin-top: 16px;
                flex-direction: row;
                gap: 12px;
                flex-wrap: wrap;
            }

            .security-badge {
                font-size: 11px;
                padding: 6px 12px;
            }

            .security-badge i { font-size: 12px; }

            .alert-message {
                padding: 12px;
                font-size: 13px;
                margin-bottom: 16px;
            }

            .alert-message i { font-size: 16px; }
        }

        /* ========================================
           EXTRA SMALL (≤375px)
           ======================================== */
        @media (max-width: 375px) {
            .left-panel {
                padding: 20px 16px 18px;
                gap: 12px;
            }

            .logo-container { gap: 12px; }

            .logo-wrapper {
                width: 54px;
                height: 54px;
            }

            .logo-img {
                width: 38px;
                height: 38px;
            }

            .brand-title { font-size: 19px; }
            .brand-subtitle { font-size: 11px; }

            .right-panel {
                padding: 22px 16px 28px;
            }

            .form-title { font-size: 23px; }
            .form-group { margin-bottom: 16px; }
            .form-control { padding: 12px 14px 12px 40px; }
            .input-icon { left: 12px; font-size: 15px; }
        }

        /* ========================================
           LANDSCAPE MOBILE
           ======================================== */
        @media (max-width: 768px) and (orientation: landscape) {
            .login-wrapper {
                grid-template-columns: 260px 1fr;
                grid-template-rows: 1fr;
                min-height: 100vh;
                min-height: 100dvh;
            }

            .left-panel {
                flex-direction: column;
                text-align: center;
                padding: 16px;
                border-radius: 0;
                gap: 0;
            }

            .logo-container {
                flex-direction: column;
                gap: 10px;
            }

            .brand-info { text-align: center; }

            .logo-wrapper {
                width: 60px;
                height: 60px;
                margin: 0 auto;
            }

            .logo-img { width: 42px; height: 42px; }
            .brand-title { font-size: 18px; }
            .brand-subtitle { font-size: 10px; }
            .decorative-line { display: block; margin: 6px auto; width: 30px; }

            .right-panel {
                padding: 14px 20px;
                overflow-y: auto;
            }

            .form-header { margin-bottom: 12px; }
            .form-title { font-size: 20px; margin-bottom: 4px; }
            .form-subtitle { font-size: 12px; }
            .login-form { margin-top: 12px; }
            .form-group { margin-bottom: 10px; }
            .form-label { margin-bottom: 4px; font-size: 11px; }
            .form-control { padding: 10px 14px 10px 40px; }
            .submit-btn { padding: 12px; font-size: 13px; }
            .divider { margin: 10px 0; }
            .security-badges { margin-top: 8px; gap: 8px; }
            .security-badge { padding: 4px 10px; font-size: 10px; }
        }

        /* ========================================
           MEJORAS PARA iOS
           ======================================== */
        @supports (-webkit-touch-callout: none) {
            .form-control {
                font-size: 16px; /* Prevent zoom */
            }
        }

        /* Safe areas iOS */
        @supports (padding: env(safe-area-inset-bottom)) {
            @media (max-width: 768px) {
                .right-panel {
                    padding-bottom: calc(24px + env(safe-area-inset-bottom));
                }
            }
        }

        /* Evitar scroll bounce en iOS - solo desktop */
        @media (min-width: 769px) {
            body {
                overscroll-behavior: none;
            }
            .login-wrapper {
                overscroll-behavior: contain;
            }
        }

        /* Touch feedback mejorado */
        @media (hover: none) and (pointer: coarse) {
            .submit-btn,
            .toggle-password,
            .forgot-password,
            .support-link a {
                -webkit-tap-highlight-color: rgba(156, 167, 119, 0.2);
            }

            .submit-btn:hover {
                transform: none;
            }

            .submit-btn:active {
                transform: scale(0.98);
            }
        }
    </style>
</head>
<body>
    
    <!-- ========================================
         CONTENEDOR PRINCIPAL
         ======================================== -->
    <div class="login-wrapper"
         data-hotel-id="<?= htmlspecialchars((string) ($loginHotelId ?? ''), ENT_QUOTES, 'UTF-8') ?>"
         data-hotel-slug="<?= htmlspecialchars((string) ($loginHotelSlug ?? ''), ENT_QUOTES, 'UTF-8') ?>"
         data-hotel-nombre="<?= htmlspecialchars((string) $loginHotelNombre, ENT_QUOTES, 'UTF-8') ?>">
        
        <!-- ========================================
             PANEL IZQUIERDO - BRANDING
             ======================================== -->
        <div class="left-panel">
            <!-- Formas decorativas de fondo -->
            <div class="shape-bg shape-1"></div>
            <div class="shape-bg shape-2"></div>
            <div class="shape-bg shape-3"></div>
            <div class="geometric-pattern"></div>
            
            <!-- Contenedor del logo -->
            <div class="logo-container">
                <div class="logo-wrapper">
                    <!-- Logo del Los Cedros -->
                    <img src="<?= asset('img/logo-hotel-san-nicolas2.png') ?>" alt="<?= htmlspecialchars($loginHotelNombre, ENT_QUOTES, 'UTF-8') ?>" class="logo-img">
                </div>
                
                <div class="brand-info">
                    <h1 class="brand-title"><?= htmlspecialchars($loginHotelNombre, ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="decorative-line"></div>
                    <p class="brand-subtitle">Sistema integral de gestión hotelera</p>
                </div>
            </div>
        </div>
        
        <!-- ========================================
             PANEL DERECHO - FORMULARIO
             ======================================== -->
        <div class="right-panel">
            <!-- Decoraciones -->
            <div class="corner-decoration top-left"></div>
            <div class="corner-decoration bottom-right"></div>
            
            <div class="form-container">
                <!-- Encabezado del formulario -->
                <div class="form-header">
                    <h2 class="form-title">Iniciar Sesión</h2>
                    <p class="form-subtitle">Ingresa tus credenciales para continuar</p>
                </div>
                
                <!-- Mensajes de alerta -->
                <?php if ($mensaje = get_mensaje()): ?>
                    <?php $tipo_mensaje = in_array($mensaje['tipo'] ?? 'info', ['error', 'success', 'info'], true) ? $mensaje['tipo'] : 'info'; ?>
                    <div class="alert-message alert-<?= htmlspecialchars($tipo_mensaje, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas <?php 
                            echo $tipo_mensaje === 'error' ? 'fa-exclamation-triangle' :
                                ($tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-info-circle');
                        ?>"></i>
                        <span><?= htmlspecialchars($mensaje['texto'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Formulario de login -->
                <form action="<?= htmlspecialchars($loginAction, ENT_QUOTES, 'UTF-8') ?>" method="POST" class="login-form" id="loginForm">
                    <?= csrf_field() ?>
                    <?php if ($loginHotelId): ?>
                        <input type="hidden" name="hotel_id_context" value="<?= htmlspecialchars((string) $loginHotelId, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    
                    <!-- Campo de Usuario -->
                    <div class="form-group">
                        <label for="nombre_usuario" class="form-label">Usuario</label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                id="nombre_usuario" 
                                name="nombre_usuario" 
                                <?= $loginDisabled ? 'disabled' : 'required' ?>
                                autocomplete="username"
                                class="form-control"
                                placeholder="Ingresa tu usuario"
                            >
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>
                    
                    <!-- Campo de Contraseña -->
                    <div class="form-group">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                <?= $loginDisabled ? 'disabled' : 'required' ?>
                                autocomplete="current-password"
                                class="form-control"
                                placeholder="Ingresa tu contraseña"
                            >
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" onclick="togglePassword()" class="toggle-password" aria-label="Mostrar contraseña">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Recordarme y Olvidé contraseña -->
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" <?= $loginDisabled ? 'disabled' : '' ?>>
                            <span>Recordarme</span>
                        </label>
                        <a href="#" class="forgot-password">¿Olvidaste tu contraseña?</a>
                    </div>
                    
                    <!-- Botón de envío -->
                    <button type="submit" class="submit-btn" id="submitBtn" <?= $loginDisabled ? 'disabled' : '' ?>>
                        Iniciar Sesión
                    </button>
                </form>
                
                <!-- Divisor -->
                <div class="divider">
                    <div class="divider-line"></div>
                    <span class="divider-text">o</span>
                    <div class="divider-line"></div>
                </div>
                
                <!-- Badges de seguridad -->
                <div class="security-badges">
                    <div class="security-badge">
                        <i class="fas fa-lock"></i>
                        <span>Conexión segura</span>
                    </div>
                    <div class="security-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>Datos encriptados</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ========================================
         SCRIPTS
         ======================================== -->
    <script>
        // ========================================
        // Toggle password visibility
        // ========================================
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // ========================================
        // Auto-focus en desktop
        // ========================================
        window.addEventListener('load', function() {
            const userInput = document.getElementById('nombre_usuario');
            if (userInput && window.innerWidth > 768) {
                setTimeout(() => userInput.focus(), 100);
            }
        });
        
        // ========================================
        // Form submission animation
        // ========================================
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Conectando...';
        });
        
        // ========================================
        // Prevenir zoom en mobile
        // ========================================
        document.addEventListener('touchstart', function(event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        }, { passive: false });
        
        // Prevenir gesture zoom en iOS
        document.addEventListener('gesturestart', function(e) {
            e.preventDefault();
        });
        
        // ========================================
        // Validación en tiempo real
        // ========================================
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#ef4444';
                } else {
                    this.style.borderColor = '#9CA777'; /* Verde olivo */
                }
            });
            
            input.addEventListener('focus', function() {
                this.style.borderColor = 'var(--olive-green)';
            });
            
            input.addEventListener('input', function() {
                if (this.style.borderColor === 'rgb(239, 68, 68)') {
                    this.style.borderColor = 'var(--border-color)';
                }
            });
        });
        
        // ========================================
        // Enter para submit
        // ========================================
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const activeElement = document.activeElement;
                if (activeElement.tagName === 'INPUT' && activeElement.id !== 'submitBtn') {
                    e.preventDefault();
                    const form = activeElement.closest('form');
                    if (form) {
                        const submitBtn = form.querySelector('[type="submit"]');
                        if (submitBtn && !submitBtn.disabled) {
                            submitBtn.click();
                        }
                    }
                }
            }
        });
        
        // ========================================
        // Animación sutil en inputs
        // ========================================
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // ========================================
        // Scroll al input activo en mobile
        // (reemplaza los hacks de viewport)
        // ========================================
        if (window.innerWidth <= 768) {
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    // Dar tiempo al teclado para aparecer y luego scroll suave
                    setTimeout(() => {
                        this.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 300);
                });
            });
        }
    </script>

</body>
</html>
