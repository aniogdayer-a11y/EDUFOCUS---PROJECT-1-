<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pixel Art Buttons - EduFocus</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Press Start 2P', cursive;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            gap: 2rem;
            margin: 0;
        }

        .pixel-btn-wrapper {
            position: relative;
            display: inline-block;
        }

        .pixel-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 12px 24px;
            font-family: 'Press Start 2P', cursive;
            font-size: 12px;
            text-transform: uppercase;
            text-decoration: none;
            
            /* Default state (Black) */
            background-color: #000;
            color: #fff;
            
            /* Simple border and shadow */
            border: 4px solid #000;
            box-shadow: inset 0 0 0 2px #fff, 0 6px 0 0 #000;
            
            cursor: pointer;
            transition: background-color 0.1s, color 0.1s, box-shadow 0.1s, transform 0.1s;
        }

        .pixel-btn svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        /* Hover State - Simple Inversion */
        .pixel-btn:hover {
            background-color: #fff;
            color: #000;
            box-shadow: inset 0 0 0 2px #000, 0 6px 0 0 #000;
        }

        /* Pressed State - Simple push down */
        .pixel-btn:active {
            transform: translateY(6px);
            background-color: #e0e0e0; /* light gray */
            color: #000;
            box-shadow: inset 0 0 0 2px #000, 0 0px 0 0 #000;
        }
    </style>
</head>
<body>

    <h2 style="font-family: sans-serif; color: #666; margin-bottom: 2rem;">Simplified Hover & Click!</h2>

    <!-- GET STARTED -->
    <div class="pixel-btn-wrapper">
        <button class="pixel-btn">
            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            Get Started
        </button>
    </div>

    <!-- LEARN MORE -->
    <div class="pixel-btn-wrapper">
        <button class="pixel-btn">
            <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9H9V9h10v2zm-4 4H9v-2h6v2zm4-8H9V5h10v2z"/></svg>
            Learn More
        </button>
    </div>

    <!-- LOG IN -->
    <div class="pixel-btn-wrapper">
        <button class="pixel-btn">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            Log In
        </button>
    </div>

</body>
</html>
