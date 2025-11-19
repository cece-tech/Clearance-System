<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth Page</title>
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: Nunito, sans-serif;
            background: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            width: 380px;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .tabs {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 25px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: bold;
        }
        .tab.active {
            border-color: #2563eb;
            color: #2563eb;
        }
        form {
            display: none;
        }
        form.active {
            display: block;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        small {
            display: block;
            margin-top: 10px;
            text-align: center;
            color: #666;
        }
    </style>
</head>

<body>

<div class="container">
    <div class="tabs">
        <div class="tab active" id="loginTab">Login</div>
        <div class="tab" id="registerTab">Register</div>
    </div>

    {{-- LOGIN FORM --}}
    <form id="loginForm" class="active" action="{{ url('/api/auth/login') }}" method="POST">
        @csrf
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Login</button>
    </form>

    {{-- REGISTER FORM --}}
    <form id="registerForm" action="{{ url('/api/auth/register') }}" method="POST">
    <label>Last Name:</label>
    <input type="text" name="last_name" required><br>

    <label>First Name:</label>
    <input type="text" name="first_name" required><br>

    <label>Middle Initial:</label>
    <input type="text" name="middle_initial" maxlength="1"><br>

    <label>Student ID (7 digits):</label>
    <input type="text" name="student_id" maxlength="7" pattern="\d{7}"><br>

    <label>Email:</label>
    <input type="email" name="email" required><br>

    <label>Password:</label>
    <input type="password" name="password" required><br>

    <label>Confirm Password:</label>
    <input type="password" name="password_confirmation" required><br>

   <input type="hidden" name="role" value="student">


    <button type="submit">Register</button>
</form>


<script>
    // Tab switching
    const loginTab = document.getElementById('loginTab');
    const registerTab = document.getElementById('registerTab');

    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    loginTab.onclick = () => {
        loginTab.classList.add('active');
        registerTab.classList.remove('active');
        loginForm.classList.add('active');
        registerForm.classList.remove('active');
    };

    registerTab.onclick = () => {
        registerTab.classList.add('active');
        loginTab.classList.remove('active');
        registerForm.classList.add('active');
        loginForm.classList.remove('active');
    };
</script>

</body>
</html>
