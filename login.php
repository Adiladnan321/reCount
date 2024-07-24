<?PHP
session_start();
require_once "database.php";

if(isset($_POST['login'])||isset($_SESSION["user"])){
            $username = $_POST['username'];
            $password = $_POST['password'];
            

            $sql = "SELECT * FROM users WHERE username ='$username'";
            $result = mysqli_query($conn,$sql);
            

            if($result)
			{
				if($result && mysqli_num_rows($result) > 0)
				{
                    $user_data = mysqli_fetch_assoc($result);
					
					if($user_data['password'] === $password)
					{
                        $_SESSION['user']=true;
						$_SESSION['user_name'] = $user_data['username'];
						header("Location: index.php");
						die();
					}
				}
			}
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReCount</title>
    <style>
        *{
            margin: 0;
            border: 0;
            box-sizing: border-box;
            font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        a{
            text-decoration: none;
            color: #007be5;
        }
        a:hover{
            text-decoration: underline;
        }
        i{
            font-style: normal;
        }
        h2{
            font-size: 30px;
            font-weight:500;
        }
        .container{
            width: 100%;
            height: 100vh;
            display: grid;
            grid-template-columns: repeat(1,1fr);
            grid-template-rows: 50px 1fr;
            align-items: center;
        }
        .logo{
            width:12rem;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

        }
        .login{
            width: 438px;
            height: 480px;
            display:flex;
            flex-direction: column;
            justify-content:center;
            align-items: center;
            /* border: 2px solid black; */
            margin: 0 auto;
        }
        .login--input{
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        
            & .or{
                height: 60px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
        }
        .login--with--google{
           
            margin-bottom: 1rem;
        
        }
        form{
             display: flex;
             flex-direction: column;
             align-items: center;
             gap: 16px;
        
             & input{
                width: 358px;
                height: 50px;
                border: 2px solid black;
                border-radius: 8px;
                padding-left: 8px;
                font-size: 15.5px;
                font-weight:450;
                color:rgb(0, 0, 0);
                letter-spacing:0.6px;
                outline-color: #007be5;
             
             }::placeholder{
                font-size: 15.5px;
                font-weight:400;
                color: #9c9595;
             }
             & button{
                width: 358px;
                height: 50px;
                border-radius: 8px;
                background: #242323;
                color: white;
                font-size: 20px;
                font-weight: 650;
                letter-spacing: 0.5px;
                cursor: pointer;
             }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="logo">
            <!-- <img src="logob.png"> -->
        </div>
        <div class="login">
            <img src="./assets/logob.png" style="width:12rem" class="logo">
            <h2>Sign in to reCount</h2>
            <div class="login--input">
                <div class="login--with--google">

                    
                </div>
             
                <form action="login.php" method="POST">
                    <input type="text" name="username" placeholder="Username">
                    <input type="password" name="password" placeholder="Password">
                    <button type="submit" name="login">Log in</button>
                    <i style="flex-direction: row; gap:5px;">No account?<a href="#">Create one</a></i>
                </form>
         
            </div>
        </div>
    </div>
</body>
</html>
