<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>服务管理面板 (Service Panel)</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen text-slate-800">

    <!-- Header -->
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold">SP
                </div>
                <h1 class="text-xl font-semibold text-slate-900">系统服务管理</h1>
            </div>
            <div class="text-sm text-slate-500">
                当前状态: <span class="text-green-600 font-medium">在线</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-6 py-10">

        <div class="mb-6 flex justify-between items-end">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">服务列表</h2>
                <p class="text-slate-500 mt-1">监控与管理服务器核心组件运行状态</p>
            </div>
            <button onclick="fetchServices()"
                class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm font-medium transition-colors">
                刷新列表
            </button>
        </div>

        <div id="service-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Loading State -->
            <div class="col-span-full text-center py-20 text-slate-400">
                正在加载服务数据...
            </div>
        </div>

    </main>

    <!-- JS Logic -->
    <script>
        async function fetchServices() {
            const grid = document.getElementById('service-grid');
            try {
                const res = await fetch('api.php?action=list');
                const json = await res.json();

                if (json.status === 'success') {
                    renderServices(json.data);
                } else {
                    grid.innerHTML = `<div class="col-span-full text-center text-red-500">加载失败: ${json.message}</div>`;
                }
            } catch (e) {
                grid.innerHTML = `<div class="col-span-full text-center text-red-500">网络错误</div>`;
            }
        }

        function renderServices(services) {
            const grid = document.getElementById('service-grid');
            grid.innerHTML = '';

            services.forEach(service => {
                const isRunning = service.status === 'running';
                const statusColor = isRunning ? 'text-green-600' : 'text-slate-500';
                const statusBg = isRunning ? 'bg-green-100' : 'bg-slate-100';
                const statusText = isRunning ? '运行中 (Running)' : '已停止 (Stopped)';
                const dotColor = isRunning ? 'bg-green-500' : 'bg-slate-400';

                const card = document.createElement('div');
                card.className = "bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col transition-all hover:shadow-md";

                card.innerHTML = `
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-semibold text-lg text-slate-900 tracking-tight">${service.name}</h3>
                            <p class="text-sm text-slate-500 mt-1">${service.description}</p>
                        </div>
                        <div class="h-10 w-10 rounded-full ${statusBg} flex items-center justify-center shrink-0">
                            <!-- Icon placeholder -->
                            <div class="h-2.5 w-2.5 rounded-full ${dotColor} ${isRunning ? 'animate-pulse' : ''}"></div>
                        </div>
                    </div>

                    <div class="mt-auto pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-sm font-medium ${statusColor} flex items-center gap-2">
                             ${statusText}
                        </span>
                        
                        <div class="flex gap-2">
                            ${isRunning
                        ? `<button onclick="toggleService(${service.id}, 'stopped')" class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-sm font-medium transition-colors">停止</button>`
                        : `<button onclick="toggleService(${service.id}, 'running')" class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 shadow-sm rounded-lg text-sm font-medium transition-colors">启动</button>`
                    }
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        async function toggleService(id, targetStatus) {
            // Optimistic UI update or just wait for reload? 
            // Better to show loading or wait. I'll simple reload after.
            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle', id, status: targetStatus })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    // Refresh
                    fetchServices();
                } else {
                    alert('操作失败: ' + json.message);
                }
            } catch (e) {
                alert('网络请求失败');
            }
        }

        // Init
        fetchServices();
    </script>
</body>

</html>