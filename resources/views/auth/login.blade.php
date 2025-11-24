<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TailorCraft</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="h-full bg-gray-50">
    <div class="min-h-full flex">
        <!-- Left Side - Form -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-cut text-white text-xl"></i>
                        </div>
                        <span class="text-3xl font-bold gradient-text">TailorCraft</span>
                    </div>
                    <h2 class="mt-8 text-3xl font-bold text-gray-900">
                        Sign in to your account
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Or{' '}
                        <a href="{{ route('register') }}" class="font-medium text-purple-600 hover:text-purple-500">
                            create a new account
                        </a>
                    </p>
                </div>

                <div class="mt-8">
                    <div class="mt-6">
                        <form method="POST" action="{{ route('login') }}" class="space-y-6">
                            @csrf
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    Email address
                                </label>
                                <div class="mt-1">
                                    <input id="email" name="email" type="email" autocomplete="email" required 
                                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                                </div>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">
                                    Password
                                </label>
                                <div class="mt-1">
                                    <input id="password" name="password" type="password" autocomplete="current-password" required 
                                           class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input id="remember" name="remember" type="checkbox" 
                                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                    <label for="remember" class="ml-2 block text-sm text-gray-900">
                                        Remember me
                                    </label>
                                </div>

                                <div class="text-sm">
                                    <a href="{{ route('password.request') }}" class="font-medium text-purple-600 hover:text-purple-500">
                                        Forgot your password?
                                    </a>
                                </div>
                            </div>

                            <div>
                                <button type="submit" 
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200">
                                    Sign in
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Illustration -->
        <div class="hidden lg:block relative w-0 flex-1">
            <div class="gradient-bg absolute inset-0 h-full w-full"></div>
            <div class="absolute inset-0 flex items-center justify-center p-12">
                <div class="text-center text-white">
                    <div class="w-64 h-64 bg-white bg-opacity-10 rounded-3xl backdrop-blur-sm p-8 mx-auto mb-8">
                        <i class="fas fa-tshirt text-6xl text-white opacity-80"></i>
                    </div>
                    <h3 class="text-3xl font-bold mb-4">Create Your Fashion Masterpiece</h3>
                    <p class="text-xl opacity-90 max-w-md mx-auto">
                        Design custom clothing with our intuitive studio. Choose fabrics, customize measurements, and bring your vision to life.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
