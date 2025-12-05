<?php

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hola Mundo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #1e8ee9, #1565c0);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.8s ease-out;
        }
        
        h1 {
            color: #333;
            font-size: 4rem;
            margin-bottom: 20px;
            background: linear-gradient(90deg, #1e8ee9, #1565c0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .subtitle {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        
        .emoji {
            font-size: 3rem;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        .btn {
            display: inline-block;
            background: #1e8ee9;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            margin: 10px;
        }
        
        .btn:hover {
            background: #1565c0;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 142, 233, 0.3);
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
                margin: 20px;
            }
            
            h1 {
                font-size: 3rem;
            }
            
            .emoji {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="emoji">👋</div>
        <h1>¡Hola Mundo!</h1>
        <p class="subtitle">Bienvenido a esta página de saludo</p>
        <div>
            <button class="btn" onclick="cambiarSaludo()">Cambiar Saludo</button>
            <button class="btn" onclick="cambiarColor()">Cambiar Color</button>
        </div>
    </div>

    <script>
        const saludos = [
            "¡Hola Mundo!",
            "Hello World!",
            "Bonjour Monde!",
            "Hallo Welt!",
            "Ciao Mondo!",
            "Olá Mundo!",
            "こんにちは世界！"
        ];
        
        const colores = [
            ["#1e8ee9", "#1565c0"], // Azul
            ["#ff6b6b", "#ee5a52"], // Rojo
            ["#51cf66", "#40c057"], // Verde
            ["#ffd43b", "#fcc419"], // Amarillo
            ["#cc5de8", "#be4bdb"], // Púrpura
            ["#ff922b", "#fd7e14"]  // Naranja
        ];
        
        let indiceSaludo = 0;
        let indiceColor = 0;
        
        function cambiarSaludo() {
            const titulo = document.querySelector('h1');
            const emoji = document.querySelector('.emoji');
            
            indiceSaludo = (indiceSaludo + 1) % saludos.length;
            titulo.textContent = saludos[indiceSaludo];
            
            // Cambiar emoji según idioma
            const emojis = ["👋", "🌍", "🎉", "🚀", "💻", "🌟", "🎯"];
            emoji.textContent = emojis[indiceSaludo];
            
            // Animación
            titulo.style.animation = 'none';
            setTimeout(() => {
                titulo.style.animation = 'fadeIn 0.8s ease-out';
            }, 10);
        }
        
        function cambiarColor() {
            const body = document.querySelector('body');
            const botones = document.querySelectorAll('.btn');
            
            indiceColor = (indiceColor + 1) % colores.length;
            const [color1, color2] = colores[indiceColor];
            
            // Cambiar gradiente del fondo
            body.style.background = `linear-gradient(135deg, ${color1}, ${color2})`;
            
            // Cambiar color de botones
            botones.forEach(boton => {
                boton.style.background = color1;
                boton.addEventListener('mouseenter', function() {
                    this.style.background = color2;
                });
                boton.addEventListener('mouseleave', function() {
                    this.style.background = color1;
                });
            });
            
            // Cambiar gradiente del título
            const titulo = document.querySelector('h1');
            titulo.style.background = `linear-gradient(90deg, ${color1}, ${color2})`;
            titulo.style.webkitBackgroundClip = 'text';
            titulo.style.backgroundClip = 'text';
        }
    </script>
</body>
</html>