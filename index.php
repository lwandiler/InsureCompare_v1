<?php
session_start();

// Database files
$usersFile = 'users.json';
$submissionsFile = 'submissions.json';
$insurersFile = 'insurers.json';
$affiliatesFile = 'affiliates.json';

// Initialize databases
if (!file_exists($usersFile)) {
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $users = [
        'admin' => [
            'username' => 'admin',
            'password' => $adminPassword,
            'role' => 'admin',
            'email' => 'admin@insurecompare.com'
        ]
    ];
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
}

if (!file_exists($submissionsFile)) {
    file_put_contents($submissionsFile, json_encode(['submissions' => []]));
}

if (!file_exists($insurersFile)) {
    // Sample insurer data based on 2025 market trends
    $insurers = [
        [
            'id' => 'ins1',
            'name' => 'SafeGuard Insurance',
            'logo' => 'safeguard.png',
            'rating' => 4.7,
            'premium_range' => ['min' => 780, 'max' => 1100],
            'coverage' => ['collision', 'comprehensive', 'roadside'],
            'affiliate_link' => 'https://affiliate.com/safeguard?ref=inscomp',
            'commission_rate' => 0.12,
            'coverage_types' => ['auto', 'home']
        ],
        [
            'id' => 'ins2',
            'name' => 'Premier Protect',
            'logo' => 'premier.png',
            'rating' => 4.5,
            'premium_range' => ['min' => 850, 'max' => 1200],
            'coverage' => ['collision', 'liability', 'rental'],
            'affiliate_link' => 'https://affiliate.com/premier?ref=inscomp',
            'commission_rate' => 0.15,
            'coverage_types' => ['auto']
        ],
        [
            'id' => 'ins3',
            'name' => 'ValueCover',
            'logo' => 'valuecover.png',
            'rating' => 4.3,
            'premium_range' => ['min' => 650, 'max' => 920],
            'coverage' => ['liability', 'personal_injury', 'uninsured'],
            'affiliate_link' => 'https://affiliate.com/valuecover?ref=inscomp',
            'commission_rate' => 0.10,
            'coverage_types' => ['auto', 'health']
        ]
    ];
    file_put_contents($insurersFile, json_encode($insurers, JSON_PRETTY_PRINT));
}

if (!file_exists($affiliatesFile)) {
    // Affiliate partners based on 2025 comparison platforms
    $affiliates = [
        [
            'id' => 'aff1',
            'name' => 'Insurify',
            'rating' => 4.8,
            'trustpilot_reviews' => 5967,
            'commission_rate' => 0.08,
            'url' => 'https://www.insurify.com'
        ],
        [
            'id' => 'aff2',
            'name' => 'Policygenius',
            'rating' => 4.7,
            'trustpilot_reviews' => 5917,
            'commission_rate' => 0.07,
            'url' => 'https://www.policygenius.com'
        ],
        [
            'id' => 'aff3',
            'name' => 'Compare.com',
            'rating' => 4.7,
            'trustpilot_reviews' => 96,
            'commission_rate' => 0.09,
            'url' => 'https://www.compare.com'
        ]
    ];
    file_put_contents($affiliatesFile, json_encode($affiliates, JSON_PRETTY_PRINT));
}

// Load data
$users = json_decode(file_get_contents($usersFile), true) ?: [];
$submissions = json_decode(file_get_contents($submissionsFile), true) ?: ['submissions' => []];
$insurers = json_decode(file_get_contents($insurersFile), true) ?: [];
$affiliates = json_decode(file_get_contents($affiliatesFile), true) ?: [];

// Handle authentication and submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
            $_SESSION['user'] = $users[$username];
            $_SESSION['user']['username'] = $username;
            
            $response = ['success' => true, 'message' => 'Login successful!'];
        } else {
            $response = ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    if ($_POST['action'] === 'submit_insurance') {
        $response = ['success' => false, 'message' => ''];
        
        try {
            $data = [
                'id' => uniqid(),
                'timestamp' => date('Y-m-d H:i:s'),
                'user' => $_SESSION['user']['username'] ?? 'guest',
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'zip_code' => $_POST['zip_code'] ?? '',
                'current_insurer' => $_POST['current_insurer'] ?? '',
                'current_premium' => floatval($_POST['current_premium'] ?? 0),
                'coverage_type' => $_POST['coverage_type'] ?? 'auto',
                'vehicle_year' => intval($_POST['vehicle_year'] ?? 0),
                'vehicle_make' => $_POST['vehicle_make'] ?? '',
                'vehicle_model' => $_POST['vehicle_model'] ?? '',
                'coverage_level' => $_POST['coverage_level'] ?? 'standard'
            ];
            
            // Add to database
            $submissions['submissions'][] = $data;
            file_put_contents($submissionsFile, json_encode($submissions, JSON_PRETTY_PRINT));
            
            $response = [
                'success' => true,
                'message' => 'Your insurance details have been submitted!',
                'submission_id' => $data['id']
            ];
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Prepare data for frontend
$isAdmin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
$isLoggedIn = isset($_SESSION['user']);

// Prepare JSON for JavaScript
$insurers_json = json_encode($insurers);
$insurers_escaped = htmlspecialchars($insurers_json, ENT_QUOTES, 'UTF-8');

$submissions_json = json_encode($submissions['submissions']);
$submissions_escaped = htmlspecialchars($submissions_json, ENT_QUOTES, 'UTF-8');

$affiliates_json = json_encode($affiliates);
$affiliates_escaped = htmlspecialchars($affiliates_json, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InsureCompare - Find Better Insurance Deals</title>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#1d4ed8',
                        accent: '#3b82f6',
                        background: '#f3f4f6',
                        card: '#ffffff',
                        savings: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
            color: #1f2937;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        
        .header-gradient {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }
        
        .savings-badge {
            background-color: #10b98120;
            color: #10b981;
            border: 1px solid #10b98140;
        }
        
        .warning-badge {
            background-color: #f59e0b20;
            color: #f59e0b;
            border: 1px solid #f59e0b40;
        }
        
        .danger-badge {
            background-color: #ef444420;
            color: #ef4444;
            border: 1px solid #ef444440;
        }
        
        .modal-overlay {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }
        
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.4s ease;
        }
        
        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .success-notification {
            background: #10b981;
            color: white;
        }
        
        .error-notification {
            background: #ef4444;
            color: white;
        }
        
        .insurer-logo {
            height: 40px;
            object-fit: contain;
        }
        
        .coverage-tag {
            display: inline-block;
            background: #dbeafe;
            color: #3b82f6;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.75rem;
            margin-right: 4px;
            margin-bottom: 4px;
        }
        
        .fade-enter-active, .fade-leave-active {
            transition: opacity 0.3s;
        }
        .fade-enter, .fade-leave-to {
            opacity: 0;
        }
        
        .scale-enter-active, .scale-leave-active {
            transition: all 0.3s ease;
        }
        .scale-enter, .scale-leave-to {
            opacity: 0;
            transform: scale(0.95);
        }
        
        .ad-container {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px dashed #93c5fd;
            border-radius: 12px;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
            font-weight: 500;
        }
        
        .result-card {
            border-left: 4px solid;
        }
        
        .good-deal {
            border-left-color: #10b981;
        }
        
        .fair-deal {
            border-left-color: #f59e0b;
        }
        
        .bad-deal {
            border-left-color: #ef4444;
        }
    </style>
</head>
<body class="min-h-screen" x-data="app()" x-init="init(<?= $insurers_escaped ?>, <?= $submissions_escaped ?>, <?= $affiliates_escaped ?>, <?= $isAdmin ? 'true' : 'false' ?>, <?= $isLoggedIn ? 'true' : 'false' ?>)">
    <!-- Header -->
    <header class="header-gradient py-4 shadow-lg sticky top-0 z-20">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center">
                <div class="bg-white p-2 rounded-lg mr-3 shadow-lg shadow-blue-500/30">
                    <i class="fas fa-shield-alt text-2xl text-primary"></i>
                </div>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-white">InsureCompare</h1>
                    <p class="text-blue-100 text-xs md:text-sm">Find Better Insurance Deals</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <template x-if="isLoggedIn">
                    <div class="flex items-center">
                        <div class="flex items-center bg-blue-700 rounded-lg px-3 py-1.5 mr-2 cursor-pointer">
                            <i class="fas fa-user-circle text-white mr-2"></i>
                            <span class="text-white" x-text="username"></span>
                            <span x-show="isAdmin" class="bg-yellow-500 text-white text-xs ml-2 px-2 py-0.5 rounded-full">Admin</span>
                        </div>
                        <button @click="logout" class="flex items-center bg-blue-800 hover:bg-blue-900 text-white font-medium py-1 px-3 md:py-2 md:px-4 rounded-lg transition">
                            <i class="fas fa-sign-out-alt mr-1 md:mr-2 text-sm md:text-base"></i> 
                            <span class="hidden md:inline">Logout</span>
                        </button>
                    </div>
                </template>
                
                <template x-if="!isLoggedIn">
                    <div class="flex space-x-2">
                        <button @click="loginModalOpen = true" class="flex items-center bg-white text-primary font-medium py-1 px-3 md:py-2 md:px-4 rounded-lg hover:bg-blue-50 transition">
                            <i class="fas fa-sign-in-alt mr-1 md:mr-2 text-sm md:text-base"></i> 
                            <span class="hidden md:inline">Login</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-gray-800 mb-3">Stop Overpaying for Insurance</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Enter your current insurance details to see if you're getting a fair deal. 
                    We'll compare your premium against 2025 market rates and suggest better options.
                </p>
            </div>
            
            <!-- Ad Banner 1 -->
            <div class="ad-container my-6">
                <div class="text-center p-4">
                    <i class="fas fa-ad text-2xl text-blue-500 mb-2"></i>
                    <p>Advertisement Space</p>
                    <p class="text-sm text-blue-400 mt-1">Premium insurance partners</p>
                </div>
            </div>
            
            <!-- Insurance Form -->
            <div class="card p-6 mb-8">
                <form id="insurance-form" @submit.prevent="submitInsurance">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" x-model="formData.name" required 
                                   class="w-full p-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-accent focus:ring-2 focus:ring-blue-100 outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" x-model="formData.email" required 
                                   class="w-full p-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-accent focus:ring-2 focus:ring-blue-100 outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code *</label>
                            <input type="text" x-model="formData.zip_code" required 
                                   class="w-full p-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-accent focus:ring-2 focus:ring-blue-100 outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Insurer *</label>
                            <input type="text" x-model="formData.current_insurer" required 
                                   class="w-full p-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-accent focus:ring-2 focus:ring-blue-100 outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Annual Premium ($) *</label>
                            <input type="number" x-model="formData.current_premium" required min="0" step="1"
                                   class="w-full p-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-accent focus:ring-2 focus:ring-blue-100 outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Coverage Type *</label>
                            <select x-model="formData.coverage_type" required 
                                    class="w-full p-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-accent focus:ring-2 focus:ring-blue-100 outline-none transition">
                                <option value="auto">Auto Insurance</option>
                                <option value="home">Home Insurance</option>
                                <option value="health">Health Insurance</option>
                                <option value="life">Life Insurance</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Coverage Level *</label>
                            <select x-model="formData.coverage_level" required 
                                    class="w-full p-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-accent focus:ring-2 focus:ring-blue-100 outline-none transition">
                                <option value="basic">Basic</option>
                                <option value="standard">Standard</option>
                                <option value="premium">Premium</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Vehicle Info (shown only for auto insurance) -->
                    <div x-show="formData.coverage_type === 'auto'" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Year *</label>
                            <input type="number" x-model="formData.vehicle_year" min="1980" max="2025"
                                   class="w-full p-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-accent focus:ring-2 focus:ring-blue-100 outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Make *</label>
                            <input type="text" x-model="formData.vehicle_make" 
                                   class="w-full p-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-accent focus:ring-2 focus:ring-blue-100 outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Model *</label>
                            <input type="text" x-model="formData.vehicle_model" 
                                   class="w-full p-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-accent focus:ring-2 focus:ring-blue-100 outline-none transition">
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-primary hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg transition flex items-center">
                            <i class="fas fa-calculator mr-2"></i> Analyze My Insurance
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Results Section -->
            <div x-show="showResults" class="card p-6 mb-8" x-transition>
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Your Insurance Analysis</h3>
                    <p class="text-gray-600">Based on 2025 market rates in your region</p>
                </div>
                
                <!-- Result Summary -->
                <div class="result-card p-5 mb-6" :class="{
                    'good-deal': result.rating === 'good',
                    'fair-deal': result.rating === 'fair',
                    'bad-deal': result.rating === 'bad'
                }">
                    <div class="flex flex-col md:flex-row items-center justify-between">
                        <div class="flex items-center mb-4 md:mb-0">
                            <div class="mr-4">
                                <span class="text-4xl font-bold" :class="{
                                    'text-savings': result.rating === 'good',
                                    'text-warning': result.rating === 'fair',
                                    'text-danger': result.rating === 'bad'
                                }" x-text="result.rating === 'good' ? '✓' : (result.rating === 'fair' ? '~' : '⚠')"></span>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold" x-text="result.title"></h4>
                                <p class="text-gray-600" x-text="result.description"></p>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <div class="text-sm text-gray-600">Your Annual Premium</div>
                            <div class="text-2xl font-bold" x-text="'$' + currentPremium"></div>
                            <div class="mt-1" :class="{
                                'text-savings': result.savings > 0,
                                'text-danger': result.savings < 0
                            }" x-text="result.savings > 0 ? ('Save $' + result.savings + '/yr') : ('Overpaying $' + (-result.savings) + '/yr')"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Market Comparison -->
                <div class="mb-6">
                    <h4 class="text-lg font-bold text-gray-800 mb-3">2025 Market Comparison</h4>
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm text-gray-600">Your Premium</div>
                        <div class="text-sm font-medium" x-text="'$' + currentPremium"></div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                        <div class="bg-primary h-2.5 rounded-full" :style="'width: ' + result.yourPremiumPercentage + '%'"></div>
                    </div>
                    
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm text-gray-600">Average Premium</div>
                        <div class="text-sm font-medium" x-text="'$' + result.avgPremium"></div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-gray-400 h-2.5 rounded-full" :style="'width: ' + result.avgPremiumPercentage + '%'"></div>
                    </div>
                    
                    <div class="mt-3 text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span x-text="marketNote"></span>
                    </div>
                </div>
                
                <!-- Recommended Insurers -->
                <div>
                    <h4 class="text-lg font-bold text-gray-800 mb-4">Recommended Insurers</h4>
                    <p class="text-gray-600 mb-4">Based on your profile, these insurers offer better rates in 2025:</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template x-for="insurer in result.recommendedInsurers" :key="insurer.id">
                            <div class="card p-4 hover:border-primary transition">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 bg-gray-100 border rounded-lg p-2 mr-4">
                                        <div class="insurer-logo-placeholder w-16 h-16 flex items-center justify-center text-gray-400">
                                            <i class="fas fa-building text-2xl"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow">
                                        <div class="flex justify-between items-start">
                                            <h5 class="font-bold text-gray-800" x-text="insurer.name"></h5>
                                            <div class="flex items-center bg-blue-50 text-primary px-2 py-1 rounded text-sm">
                                                <i class="fas fa-star mr-1"></i>
                                                <span x-text="insurer.rating"></span>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <div class="text-lg font-bold" x-text="'$' + insurer.premium + '/yr'"></div>
                                            <div class="text-sm text-savings font-medium mt-1">
                                                <span x-text="'Save $' + (currentPremium - insurer.premium) + '/yr'"></span>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3 flex flex-wrap">
                                            <template x-for="coverage in insurer.coverage" :key="coverage">
                                                <span class="coverage-tag" x-text="coverage.replace('_', ' ')"></span>
                                            </template>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <a :href="insurer.affiliate_link" target="_blank" class="bg-primary hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-lg inline-flex items-center transition">
                                                <span>Get Quote</span>
                                                <i class="fas fa-arrow-right ml-2"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                
                <!-- Affiliate Partners -->
                <div class="mt-8">
                    <h4 class="text-lg font-bold text-gray-800 mb-4">Compare More Options</h4>
                    <p class="text-gray-600 mb-4">These top-rated comparison platforms can help you find better deals:</p>
                    
                    <div class="grid grid-cols-1 gap-3">
                        <template x-for="affiliate in affiliates" :key="affiliate.id">
                            <div class="card p-4 flex items-center justify-between">
                                <div>
                                    <h5 class="font-bold text-gray-800" x-text="affiliate.name"></h5>
                                    <div class="flex items-center mt-1">
                                        <div class="flex items-center text-yellow-400">
                                            <template x-for="i in 5" :key="i">
                                                <i :class="i <= Math.round(affiliate.rating) ? 'fas fa-star' : 'far fa-star'" class="text-sm"></i>
                                            </template>
                                        </div>
                                        <span class="text-sm text-gray-600 ml-2" x-text="affiliate.trustpilot_reviews + ' reviews'"></span>
                                    </div>
                                </div>
                                <a :href="affiliate.url" target="_blank" class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium py-2 px-4 rounded-lg transition">
                                    Visit Site
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            
            <!-- Ad Banner 2 -->
            <div class="ad-container my-6">
                <div class="text-center p-4">
                    <i class="fas fa-ad text-2xl text-blue-500 mb-2"></i>
                    <p>Advertisement Space</p>
                    <p class="text-sm text-blue-400 mt-1">Top-rated insurance providers</p>
                </div>
            </div>
            
            <!-- Admin Section -->
            <template x-if="isAdmin">
                <div class="card p-6 mt-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Admin Dashboard</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="card p-4 text-center">
                            <div class="text-3xl font-bold text-primary" x-text="submissions.length"></div>
                            <div class="text-sm text-gray-600">Total Submissions</div>
                        </div>
                        
                        <div class="card p-4 text-center">
                            <div class="text-3xl font-bold text-savings" x-text="'$' + averagePremium"></div>
                            <div class="text-sm text-gray-600">Avg. Premium</div>
                        </div>
                        
                        <div class="card p-4 text-center">
                            <div class="text-3xl font-bold text-warning" x-text="badDealPercentage + '%'"></div>
                            <div class="text-sm text-gray-600">Overpaying Customers</div>
                        </div>
                        
                        <div class="card p-4 text-center">
                            <div class="text-3xl font-bold text-primary" x-text="insurers.length"></div>
                            <div class="text-sm text-gray-600">Insurance Partners</div>
                        </div>
                    </div>
                    
                    <h4 class="text-lg font-bold text-gray-800 mb-3">Recent Submissions</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left">Name</th>
                                    <th class="py-3 px-4 text-left">Premium</th>
                                    <th class="py-3 px-4 text-left">Type</th>
                                    <th class="py-3 px-4 text-left">Date</th>
                                    <th class="py-3 px-4 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="sub in submissions.slice(0, 5)" :key="sub.id">
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-3 px-4" x-text="sub.name"></td>
                                        <td class="py-3 px-4" x-text="'$' + sub.current_premium"></td>
                                        <td class="py-3 px-4 capitalize" x-text="sub.coverage_type"></td>
                                        <td class="py-3 px-4" x-text="new Date(sub.timestamp).toLocaleDateString()"></td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 rounded-full text-xs" :class="{
                                                'savings-badge': getDealRating(sub).rating === 'good',
                                                'warning-badge': getDealRating(sub).rating === 'fair',
                                                'danger-badge': getDealRating(sub).rating === 'bad'
                                            }" x-text="getDealRating(sub).rating"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <p class="mb-2">© 2025 InsureCompare. All rights reserved.</p>
                <p class="text-gray-400 text-sm">
                    InsureCompare provides comparisons based on available market data. 
                    Always verify details with insurance providers.
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Login Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="loginModalOpen" x-cloak
         x-transition:enter="fade-enter"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="fade-leave"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="modal-overlay absolute inset-0" @click="loginModalOpen = false"></div>
        
        <div class="modal-content relative z-10 max-w-md w-full"
             @click.stop
             x-transition:enter="scale-enter"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="scale-leave"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <button @click="loginModalOpen = false" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-r from-primary to-secondary mb-3">
                        <i class="fas fa-lock text-2xl text-white"></i>
                    </div>
                    <h2 class="text-2xl font-bold">Admin Login</h2>
                </div>
                
                <form @submit.prevent="login">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Username</label>
                            <input type="text" x-model="loginUsername" required 
                                   class="w-full p-3 rounded-lg bg-gray-800 border border-gray-700 text-white transition focus:border-accent focus:outline-none"
                                   placeholder="username">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-1">Password</label>
                            <input type="password" x-model="loginPassword" required 
                                   class="w-full p-3 rounded-lg bg-gray-800 border border-gray-700 text-white transition focus:border-accent focus:outline-none"
                                   placeholder="••••••••">
                        </div>
                        
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="w-full bg-primary hover:bg-blue-700 px-4 py-3 rounded-lg text-white font-medium transition">
                                Sign In
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Notifications -->
    <div class="notification success-notification" :class="{'show': successNotification}">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-lg mr-2"></i>
            <span x-text="notificationMessage"></span>
        </div>
    </div>

    <div class="notification error-notification" :class="{'show': errorNotification}">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-lg mr-2"></i>
            <span x-text="notificationMessage"></span>
        </div>
    </div>

    <script>
        function app() {
            return {
                // App state
                insurers: [],
                submissions: [],
                affiliates: [],
                isAdmin: false,
                isLoggedIn: false,
                username: '',
                loginModalOpen: false,
                loginUsername: '',
                loginPassword: '',
                formData: {
                    name: '',
                    email: '',
                    zip_code: '',
                    current_insurer: '',
                    current_premium: 1200,
                    coverage_type: 'auto',
                    coverage_level: 'standard',
                    vehicle_year: 2020,
                    vehicle_make: 'Toyota',
                    vehicle_model: 'Camry'
                },
                showResults: false,
                result: null,
                currentPremium: 0,
                successNotification: false,
                errorNotification: false,
                notificationMessage: '',
                marketNote: '',
                
                // Computed properties
                get averagePremium() {
                    if (this.submissions.length === 0) return 0;
                    const total = this.submissions.reduce((sum, sub) => sum + sub.current_premium, 0);
                    return Math.round(total / this.submissions.length);
                },
                
                get badDealPercentage() {
                    if (this.submissions.length === 0) return 0;
                    const badDeals = this.submissions.filter(sub => {
                        const rating = this.getDealRating(sub).rating;
                        return rating === 'bad';
                    }).length;
                    return Math.round((badDeals / this.submissions.length) * 100);
                },
                
                // Methods
                init(insurersData, submissionsData, affiliatesData, adminStatus, loggedIn) {
                    this.insurers = insurersData;
                    this.submissions = submissionsData;
                    this.affiliates = affiliatesData;
                    this.isAdmin = adminStatus;
                    this.isLoggedIn = loggedIn;
                    this.username = this.isLoggedIn ? 'admin' : '';
                },
                
                login() {
                    const formData = new FormData();
                    formData.append('action', 'login');
                    formData.append('username', this.loginUsername);
                    formData.append('password', this.loginPassword);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showNotification('Login successful!', 'success');
                            this.isLoggedIn = true;
                            this.username = this.loginUsername;
                            this.loginModalOpen = false;
                        } else {
                            this.showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        this.showNotification('Login failed: ' + error.message, 'error');
                    });
                },
                
                logout() {
                    this.isLoggedIn = false;
                    this.username = '';
                    this.isAdmin = false;
                    this.showNotification('You have been logged out', 'success');
                },
                
                submitInsurance() {
                    this.currentPremium = this.formData.current_premium;
                    
                    const formData = new FormData();
                    formData.append('action', 'submit_insurance');
                    for (const [key, value] of Object.entries(this.formData)) {
                        formData.append(key, value);
                    }
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showNotification(data.message, 'success');
                            
                            // Add to local state
                            this.submissions.unshift({
                                id: data.submission_id,
                                ...this.formData,
                                timestamp: new Date().toISOString(),
                                user: this.username || 'guest'
                            });
                            
                            // Calculate result
                            this.calculateResult();
                            this.showResults = true;
                            
                            // Scroll to results
                            setTimeout(() => {
                                document.querySelector('[x-show="showResults"]').scrollIntoView({
                                    behavior: 'smooth'
                                });
                            }, 300);
                        } else {
                            this.showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        this.showNotification('Submission failed: ' + error.message, 'error');
                    });
                },
                
                calculateResult() {
                    // Get similar insurers based on coverage type
                    const similarInsurers = this.insurers.filter(insurer => 
                        insurer.coverage_types.includes(this.formData.coverage_type)
                    );
                    
                    // Calculate average premium based on 2025 market data
                    let avgPremium;
                    switch(this.formData.coverage_type) {
                        case 'auto':
                            avgPremium = 920; // 2025 US average auto premium
                            this.marketNote = "2025 auto premiums average $920/yr in the US, with regional variations";
                            break;
                        case 'home':
                            avgPremium = 1500;
                            this.marketNote = "2025 home insurance averages $1,500/yr, varying by location and property value";
                            break;
                        case 'health':
                            avgPremium = 4800;
                            this.marketNote = "Individual health insurance averages $4,800/yr in 2025";
                            break;
                        case 'life':
                            avgPremium = 350;
                            this.marketNote = "Term life insurance averages $350/yr for $500k coverage";
                            break;
                        default:
                            avgPremium = this.formData.current_premium;
                    }
                    
                    // Adjust based on coverage level
                    if (this.formData.coverage_level === 'premium') {
                        avgPremium *= 1.3;
                    } else if (this.formData.coverage_level === 'basic') {
                        avgPremium *= 0.8;
                    }
                    
                    // Calculate savings (positive means user is paying less than average)
                    const savings = avgPremium - this.formData.current_premium;
                    const percentageDiff = Math.abs(savings) / avgPremium * 100;
                    
                    // Determine rating
                    let rating, title, description;
                    
                    if (savings > 0) {
                        // User is paying less than average
                        if (percentageDiff > 20) {
                            rating = 'good';
                            title = 'Great Deal!';
                            description = 'You\'re paying significantly less than average for your coverage.';
                        } else {
                            rating = 'fair';
                            title = 'Fair Deal';
                            description = 'Your premium is slightly better than average.';
                        }
                    } else if (savings < 0) {
                        // User is paying more than average
                        if (percentageDiff > 20) {
                            rating = 'bad';
                            title = 'You Might Be Overpaying';
                            description = 'Your premium is significantly higher than market average.';
                        } else {
                            rating = 'fair';
                            title = 'Average Deal';
                            description = 'Your premium is about average for your coverage.';
                        }
                    } else {
                        // Exactly average
                        rating = 'fair';
                        title = 'Average Deal';
                        description = 'Your premium matches the market average.';
                    }
                    
                    // Get recommended insurers (top 3 cheapest for coverage type)
                    const recommendedInsurers = [...similarInsurers]
                        .sort((a, b) => a.premium_range.min - b.premium_range.min)
                        .slice(0, 3)
                        .map(insurer => ({
                            ...insurer,
                            premium: Math.round((insurer.premium_range.min + insurer.premium_range.max) / 2)
                        }));
                    
                    // Calculate percentages for the visual comparison
                    const maxPremium = Math.max(this.formData.current_premium, avgPremium * 1.5);
                    const yourPremiumPercentage = (this.formData.current_premium / maxPremium) * 100;
                    const avgPremiumPercentage = (avgPremium / maxPremium) * 100;
                    
                    // Set result
                    this.result = {
                        rating,
                        title,
                        description,
                        savings: Math.abs(savings),
                        avgPremium: Math.round(avgPremium),
                        recommendedInsurers,
                        yourPremiumPercentage: Math.min(100, yourPremiumPercentage),
                        avgPremiumPercentage: Math.min(100, avgPremiumPercentage)
                    };
                },
                
                getDealRating(submission) {
                    // Simplified version of the rating calculation for admin view
                    let avgPremium;
                    switch(submission.coverage_type) {
                        case 'auto': avgPremium = 920; break;
                        case 'home': avgPremium = 1500; break;
                        case 'health': avgPremium = 4800; break;
                        case 'life': avgPremium = 350; break;
                        default: avgPremium = submission.current_premium;
                    }
                    
                    // Adjust based on coverage level if available
                    if (submission.coverage_level === 'premium') {
                        avgPremium *= 1.3;
                    } else if (submission.coverage_level === 'basic') {
                        avgPremium *= 0.8;
                    }
                    
                    const savings = avgPremium - submission.current_premium;
                    const percentageDiff = Math.abs(savings) / avgPremium * 100;
                    
                    if (savings > 0 && percentageDiff > 20) return { rating: 'good' };
                    if (savings < 0 && percentageDiff > 20) return { rating: 'bad' };
                    return { rating: 'fair' };
                },
                
                showNotification(message, type = 'success') {
                    this.notificationMessage = message;
                    
                    if (type === 'success') {
                        this.successNotification = true;
                        setTimeout(() => {
                            this.successNotification = false;
                        }, 3000);
                    } else {
                        this.errorNotification = true;
                        setTimeout(() => {
                            this.errorNotification = false;
                        }, 3000);
                    }
                }
            };
        }
    </script>
</body>
</html>