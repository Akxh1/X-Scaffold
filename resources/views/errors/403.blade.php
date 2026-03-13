{{-- Fun, themed 403 Unauthorized page --}}
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied — X-Scaffold</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            color: #e2e8f0;
            overflow: hidden;
            position: relative;
        }

        /* Animated background particles */
        .particles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }
        .particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }
        .particle:nth-child(1) { width: 300px; height: 300px; background: #6366f1; top: -100px; left: -50px; animation-delay: 0s; }
        .particle:nth-child(2) { width: 200px; height: 200px; background: #ec4899; bottom: -80px; right: -40px; animation-delay: -5s; }
        .particle:nth-child(3) { width: 250px; height: 250px; background: #8b5cf6; top: 50%; left: 60%; animation-delay: -10s; }
        .particle:nth-child(4) { width: 150px; height: 150px; background: #f59e0b; bottom: 20%; left: 10%; animation-delay: -15s; }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -30px) scale(1.05); }
            50% { transform: translate(-20px, 20px) scale(0.95); }
            75% { transform: translate(15px, 15px) scale(1.02); }
        }

        .container {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 540px;
            padding: 2rem;
        }

        /* Shield animation */
        .shield-container {
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
        }
        .shield {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            box-shadow: 0 0 60px rgba(239, 68, 68, 0.3), 0 0 120px rgba(239, 68, 68, 0.1);
            animation: pulse-shield 2s ease-in-out infinite;
            position: relative;
        }
        .shield::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            border: 2px dashed rgba(239, 68, 68, 0.4);
            animation: spin 15s linear infinite;
        }
        @keyframes pulse-shield {
            0%, 100% { transform: scale(1); box-shadow: 0 0 60px rgba(239,68,68,0.3); }
            50% { transform: scale(1.05); box-shadow: 0 0 80px rgba(239,68,68,0.4); }
        }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .error-code {
            font-size: 5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #ef4444, #f97316, #ef4444);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 3s ease infinite;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 0.75rem;
        }

        .message {
            font-size: 1rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            margin: 0.5rem 0.25rem;
        }
        .role-yours {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .role-required {
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .roles-info {
            margin-bottom: 2rem;
            font-size: 0.85rem;
            color: #64748b;
        }

        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 14px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(99, 102, 241, 0.4);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .fun-messages {
            margin-top: 2.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .fun-messages p {
            font-size: 0.8rem;
            color: #475569;
            font-style: italic;
        }

        .countdown {
            margin-top: 1rem;
            font-size: 0.75rem;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="container">
        <div class="shield-container">
            <div class="shield">
                <i class="fas fa-lock"></i>
            </div>
        </div>

        <div class="error-code">403</div>
        <h1 class="title">Whoa there, adventurer! 🛑</h1>
        <p class="message">
            You've wandered into restricted territory. This area requires special clearance that your current role doesn't have.
        </p>

        <div class="roles-info">
            <div>
                <span class="role-badge role-yours">
                    <i class="fas fa-user"></i>
                    Your role: <strong>{{ ucfirst($userRole ?? 'unknown') }}</strong>
                </span>
            </div>
            <div style="margin-top: 0.5rem;">
                Required:
                @foreach(($requiredRoles ?? ['instructor']) as $role)
                    <span class="role-badge role-required">
                        <i class="fas fa-key"></i>
                        {{ ucfirst($role) }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="buttons">
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
            @if(($userRole ?? '') === 'student')
                <a href="{{ route('student.dashboard') }}" class="btn btn-primary">
                    <i class="fas fa-home"></i> My Dashboard
                </a>
            @elseif(in_array($userRole ?? '', ['teacher', 'admin']))
                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i> Instructor Dashboard
                </a>
            @else
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Home
                </a>
            @endif
        </div>

        <div class="fun-messages">
            <p id="funMessage"></p>
        </div>

        <div class="countdown" id="countdown"></div>
    </div>

    <script>
        const messages = [
            "🎮 This isn't the level you're looking for... try the other door!",
            "🔐 Nice try, but this vault needs a different keycard.",
            "🧭 Wrong turn! Your quest lies elsewhere, brave one.",
            "🚀 Access level insufficient. Time to level up your clearance!",
            "🎭 You shall not pass! — Gandalf (and also our middleware)",
            "🕵️ Our security guard says: 'Papers, please!' ...and yours don't check out.",
            "🏰 The castle drawbridge is up. You'll need a different invitation.",
            "🎪 This show requires a VIP ticket. Check with your admin!",
        ];
        document.getElementById('funMessage').textContent = messages[Math.floor(Math.random() * messages.length)];

        // Auto-redirect countdown
        let seconds = 15;
        const el = document.getElementById('countdown');
        const tick = () => {
            el.textContent = `Auto-redirecting in ${seconds}s...`;
            if (--seconds < 0) {
                history.back();
            } else {
                setTimeout(tick, 1000);
            }
        };
        tick();
    </script>
</body>
</html>
