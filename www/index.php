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
                当前状态: <span id="header-status" class="text-slate-400 font-medium">自检中...</span>
            </div>
        </div>
    </div>

    <!-- 目录自检失败提示区（默认隐藏，自检不通过时才显示） -->
    <div id="selfcheck-panel" class="hidden max-w-6xl mx-auto px-6 py-10"></div>

    <!-- Main Content（默认隐藏，自检通过后才渲染，避免出现一堆不可用按钮） -->
    <main id="main-content" class="hidden max-w-6xl mx-auto px-6 py-10">

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
        // ---------- 启动流程：先目录自检，再加载业务 ----------
        async function boot() {
            document.getElementById('header-status').textContent = '自检中...';
            try {
                const res = await fetch('api.php?action=selfcheck');
                const json = await res.json();

                if (json.status === 'success' && json.data && json.data.ok) {
                    showMain();
                    fetchServices();
                } else {
                    showSelfcheckFailure(json.data);
                }
            } catch (e) {
                showSelfcheckFailure(null);
            }
        }

        function setHeaderStatus(ok) {
            const el = document.getElementById('header-status');
            if (ok) {
                el.textContent = '在线';
                el.className = 'text-green-600 font-medium';
            } else {
                el.textContent = '自检未通过';
                el.className = 'text-red-600 font-medium';
            }
        }

        function showMain() {
            setHeaderStatus(true);
            document.getElementById('selfcheck-panel').classList.add('hidden');
            document.getElementById('main-content').classList.remove('hidden');
        }

        // ---------- 自检失败：只显示修复指引，不显示任何业务按钮 ----------
        function showSelfcheckFailure(data) {
            setHeaderStatus(false);
            document.getElementById('main-content').classList.add('hidden');

            const panel = document.getElementById('selfcheck-panel');
            panel.classList.remove('hidden');

            let dirRows = '';
            let constraintRows = '';

            if (data && Array.isArray(data.dirs)) {
                data.dirs.forEach(d => {
                    const badge = d.ok
                        ? '<span class="text-green-600 font-medium">正常</span>'
                        : (!d.exists
                            ? '<span class="text-red-600 font-medium">目录缺失</span>'
                            : '<span class="text-amber-600 font-medium">不可写</span>');
                    const suggestion = d.suggestion
                        ? `<code class="block mt-2 px-3 py-2 bg-slate-900 text-green-300 rounded-lg text-xs font-mono overflow-x-auto">${escapeHtml(d.suggestion)}</code>`
                        : '';
                    dirRows += `
                        <li class="py-3 ${d.ok ? '' : 'bg-red-50/60'} px-4 rounded-lg">
                            <div class="flex items-center justify-between gap-4 flex-wrap">
                                <span class="font-mono text-sm font-semibold text-slate-900">${escapeHtml(d.name)}/</span>
                                <span class="text-xs text-slate-400 font-mono hidden md:inline">${escapeHtml(d.path)}</span>
                                ${badge}
                            </div>
                            ${suggestion}
                        </li>`;
                });
            }

            if (data && Array.isArray(data.constraints)) {
                data.constraints.forEach(c => {
                    if (c.ok) return;
                    constraintRows += `
                        <li class="py-3 px-4 rounded-lg bg-red-50/60">
                            <div class="flex items-center justify-between gap-4 flex-wrap">
                                <span class="text-sm font-medium text-slate-900">${escapeHtml(c.label)}</span>
                                <span class="text-xs text-red-500 font-mono">${escapeHtml(c.path)}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">建议：${escapeHtml(c.suggestion)}</p>
                        </li>`;
                });
            }

            if (!data) {
                dirRows = '<li class="py-3 px-4 text-sm text-red-600">自检接口请求失败，请确认 PHP 服务已启动。</li>';
            }

            panel.innerHTML = `
                <div class="bg-white border border-red-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-red-100 bg-red-50 flex items-start gap-3">
                        <div class="text-2xl leading-none">⚠️</div>
                        <div>
                            <h2 class="text-lg font-bold text-red-700">目录结构自检未通过</h2>
                            <p class="text-sm text-red-500 mt-1">
                                面板已暂停加载服务功能。请按下述建议在服务器上修复目录后，点击“重新自检”。
                            </p>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <h3 class="text-sm font-semibold text-slate-700 mb-2">核心目录检查（必须存在且可写）</h3>
                        <ul class="divide-y divide-slate-100">${dirRows}</ul>
                        ${constraintRows ? `
                            <h3 class="text-sm font-semibold text-slate-700 mt-6 mb-2">路径约束检查</h3>
                            <ul class="divide-y divide-slate-100">${constraintRows}</ul>` : ''}
                        <div class="mt-6 flex items-center gap-3">
                            <button onclick="boot()"
                                class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-lg text-sm font-medium transition-colors">
                                重新自检
                            </button>
                            <span class="text-xs text-slate-400">项目根目录：${data ? escapeHtml(data.root) : '未知'}</span>
                        </div>
                    </div>
                </div>`;
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        // ---------- 业务逻辑 ----------
        async function fetchServices() {
            const grid = document.getElementById('service-grid');
            try {
                const res = await fetch('api.php?action=list');
                const json = await res.json();

                // 运行期间目录被破坏：后端返回 503，切回自检失败视图
                if (res.status === 503) {
                    showSelfcheckFailure(json.data || null);
                    return;
                }

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
            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle', id, status: targetStatus })
                });
                const json = await res.json();

                if (res.status === 503) {
                    showSelfcheckFailure(json.data || null);
                    return;
                }

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

        // Init：先自检，通过后才加载服务列表
        boot();
    </script>
</body>

</html>
