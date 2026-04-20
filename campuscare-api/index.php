<?php require_once 'components/header.php'; ?>

<!-- Navbar -->
<nav class="border-b border-[#2a2a2a] bg-[#121212]/80 backdrop-blur-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <i data-lucide="shield-check" class="w-8 h-8 text-[#13ec87]"></i>
            <span class="text-xl font-bold tracking-tight text-white">CampusCare</span>
        </div>
        <div class="hidden md:flex gap-8 text-sm font-medium text-gray-400">
            <a href="#features" class="hover:text-white transition-colors">Features</a>
            <a href="#about" class="hover:text-white transition-colors">About</a>
            <a href="#support" class="hover:text-white transition-colors">Support</a>
        </div>
        <div>
            <a href="auth/login.php" class="px-5 py-2.5 bg-[#1e1e1e] border border-[#333] hover:border-[#13ec87] text-white text-sm font-medium rounded-lg transition-all">Sign In</a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<main class="max-w-7xl mx-auto px-6 py-24 flex flex-col items-center text-center">
    <div class="inline-block px-4 py-1.5 mb-6 rounded-full border border-[#13ec87]/30 bg-[#13ec87]/10 text-[#13ec87] text-sm font-semibold tracking-wide">
        Enterprise Grade Management
    </div>
    
    <h1 class="text-5xl md:text-7xl font-extrabold text-white tracking-tight leading-tight mb-8 max-w-4xl">
        Designed for modern institutional needs with <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#13ec87] to-cyan-400">precision</span> and clarity.
    </h1>
    
    <p class="text-lg md:text-xl text-gray-400 max-w-2xl mb-12">
        CampusCare provides an uncompromising security standard and seamless workflow to resolve student complaints efficiently across all administrative levels.
    </p>
    
    <div class="flex flex-col sm:flex-row gap-4 w-full justify-center">
        <a href="auth/login.php" class="group px-8 py-4 bg-[#13ec87] text-[#121212] font-bold rounded-xl shadow-[0_0_20px_rgba(19,236,135,0.3)] hover:bg-[#0fae62] hover:shadow-[0_0_30px_rgba(19,236,135,0.5)] transition-all flex items-center justify-center gap-2">
            Access Portal
            <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
        </a>
        <a href="#features" class="px-8 py-4 bg-transparent border border-[#333] text-white font-medium rounded-xl hover:bg-[#1e1e1e] transition-colors flex items-center justify-center">
            Explore Features
        </a>
    </div>
</main>

<script>
    lucide.createIcons();
</script>

</body>
</html>
