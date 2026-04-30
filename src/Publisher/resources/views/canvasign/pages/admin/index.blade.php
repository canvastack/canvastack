<?php
/**
 * Dashboard Page — Canvasign Template
 *
 * @filesource  index.blade.php
 *
 * @author      wisnuwidi@canvastack.com
 * @copyright   wisnuwidi
 * @email       wisnuwidi@canvastack.com
 */
?>

@extends('canvasign.template.admin.index')

@section('content')

    {{-- CanvaStack dynamic content (charts, widgets, etc.) --}}
    @if (!empty($content_page))
        @foreach ($content_page as $key => $content)
            @if (!is_array($content))
                {!! $content !!}
            @else
                @if ('charts' === $key)
                    @foreach ($content as $chart)
                        {!! $chart->container() !!}
                        {!! $chart->script() !!}
                    @endforeach
                @endif
            @endif
        @endforeach
    @else

    {{-- ============================================================
         DEMO / FALLBACK DASHBOARD
         Rendered when $content_page is empty (e.g. fresh install).
         Mirrors the static design in .docs/Design/Template/canvasign/index.html
         ============================================================ --}}

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <div class="crumbs"><a href="#">Home</a> / Dashboard</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-soft btn-sm"><i class="bi bi-download me-1"></i> Export</button>
            <button class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Report</button>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-3">

        {{-- Total Revenue --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card">
                <div class="icon-wrap"><i class="bi bi-cash-stack"></i></div>
                <div class="label">Total Revenue</div>
                <div class="value">$84,295</div>
                <div class="trend up"><i class="bi bi-arrow-up-right"></i> 12.4% vs last month</div>
                <div id="spark-revenue" class="spark"></div>
            </div>
        </div>

        {{-- Active Users --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card">
                <div class="icon-wrap" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)">
                    <i class="bi bi-people"></i>
                </div>
                <div class="label">Active Users</div>
                <div class="value">12,840</div>
                <div class="trend up"><i class="bi bi-arrow-up-right"></i> 8.2% vs last month</div>
                <div id="spark-users" class="spark"></div>
            </div>
        </div>

        {{-- Orders --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card">
                <div class="icon-wrap" style="background:linear-gradient(135deg,#22c55e,#16a34a)">
                    <i class="bi bi-bag-check"></i>
                </div>
                <div class="label">Orders</div>
                <div class="value">3,427</div>
                <div class="trend up"><i class="bi bi-arrow-up-right"></i> 4.6% vs last month</div>
                <div id="spark-orders" class="spark"></div>
            </div>
        </div>

        {{-- Conversion --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card">
                <div class="icon-wrap" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="label">Conversion</div>
                <div class="value">3.84%</div>
                <div class="trend down"><i class="bi bi-arrow-down-right"></i> 0.6% vs last month</div>
                <div id="spark-conv" class="spark"></div>
            </div>
        </div>

    </div>{{-- /.row KPI Cards --}}

    {{-- Charts Row --}}
    <div class="row g-3 mb-3">

        {{-- Revenue Overview Chart --}}
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Revenue Overview</span>
                    <select class="form-select form-select-sm" style="width:auto" aria-label="Period">
                        <option>This year</option>
                        <option>Last year</option>
                    </select>
                </div>
                <div class="card-body">
                    <div id="chart-revenue" style="height:340px"></div>
                </div>
            </div>
        </div>

        {{-- Traffic Sources Chart --}}
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header">Traffic Sources</div>
                <div class="card-body">
                    <div id="chart-traffic" style="height:340px"></div>
                </div>
            </div>
        </div>

    </div>{{-- /.row Charts --}}

    {{-- Recent Orders + Activity Feed --}}
    <div class="row g-3">

        {{-- Recent Orders Table --}}
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Recent Orders</span>
                    <a href="#" class="small">View all</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#A-1024</td>
                                    <td>Sarah Johnson</td>
                                    <td>$249.00</td>
                                    <td><span class="badge bg-soft-success">Paid</span></td>
                                </tr>
                                <tr>
                                    <td>#A-1023</td>
                                    <td>Mike Chen</td>
                                    <td>$1,820.00</td>
                                    <td><span class="badge bg-soft-warning">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>#A-1022</td>
                                    <td>Emma Davis</td>
                                    <td>$78.50</td>
                                    <td><span class="badge bg-soft-success">Paid</span></td>
                                </tr>
                                <tr>
                                    <td>#A-1021</td>
                                    <td>James Wilson</td>
                                    <td>$540.00</td>
                                    <td><span class="badge bg-soft-danger">Refunded</span></td>
                                </tr>
                                <tr>
                                    <td>#A-1020</td>
                                    <td>Olivia Brown</td>
                                    <td>$320.00</td>
                                    <td><span class="badge bg-soft-info">Shipped</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activity Feed --}}
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header">Activity Feed</div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex gap-3 mb-3">
                            <div class="avatar" style="background:var(--gradient-primary)">
                                <i class="bi bi-bag"></i>
                            </div>
                            <div>
                                <strong>New order</strong> by Sarah Johnson<br>
                                <small class="text-muted">2 minutes ago</small>
                            </div>
                        </li>
                        <li class="d-flex gap-3 mb-3">
                            <div class="avatar" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div>
                                <strong>New user</strong> registered<br>
                                <small class="text-muted">12 minutes ago</small>
                            </div>
                        </li>
                        <li class="d-flex gap-3 mb-3">
                            <div class="avatar" style="background:linear-gradient(135deg,#22c55e,#16a34a)">
                                <i class="bi bi-cash"></i>
                            </div>
                            <div>
                                <strong>Payment received</strong> $1,820<br>
                                <small class="text-muted">1 hour ago</small>
                            </div>
                        </li>
                        <li class="d-flex gap-3 mb-0">
                            <div class="avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                                <i class="bi bi-chat"></i>
                            </div>
                            <div>
                                <strong>New review</strong> 5★ from Emma Davis<br>
                                <small class="text-muted">3 hours ago</small>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>{{-- /.row Recent + Activity --}}

    {{-- ECharts demo initialization (only when echarts library is available) --}}
    <script>
    (function () {
        if (typeof echarts === 'undefined') return;

        var charts = [];

        function colors() {
            var css = getComputedStyle(document.documentElement);
            return {
                text:      css.getPropertyValue('--text').trim(),
                muted:     css.getPropertyValue('--text-muted').trim(),
                grid:      css.getPropertyValue('--border').trim(),
                primary:   css.getPropertyValue('--primary').trim(),
                secondary: css.getPropertyValue('--secondary').trim(),
                success:   css.getPropertyValue('--success').trim(),
                warning:   css.getPropertyValue('--warning').trim(),
                danger:    css.getPropertyValue('--danger').trim(),
                info:      css.getPropertyValue('--info').trim(),
                surface:   css.getPropertyValue('--surface').trim(),
            };
        }

        function baseAxis(c) {
            return {
                axisLine:  { lineStyle: { color: c.grid } },
                axisLabel: { color: c.muted },
                splitLine: { lineStyle: { color: c.grid, type: 'dashed' } },
                axisTick:  { show: false },
            };
        }

        function tooltip(c) {
            return {
                backgroundColor: c.surface,
                borderColor:     c.grid,
                textStyle:       { color: c.text },
            };
        }

        function register(el, builder) {
            if (!el) return;
            var inst   = echarts.init(el);
            var render = function () { inst.setOption(builder(colors()), true); };
            render();
            charts.push({ inst: inst, render: render });
            return inst;
        }

        window.addEventListener('themechange', function () {
            setTimeout(function () { charts.forEach(function (c) { c.render(); }); }, 50);
        });
        window.addEventListener('resize', function () {
            charts.forEach(function (c) { c.inst.resize(); });
        });

        /* ---- Sparklines ---- */
        function sparkOpt(data, color) {
            return function (c) {
                return {
                    grid:   { left: 0, right: 0, top: 4, bottom: 0 },
                    xAxis:  { type: 'category', show: false, data: data.map(function (_, i) { return i; }) },
                    yAxis:  { type: 'value', show: false, scale: true },
                    series: [{
                        type: 'line', data: data, smooth: true, symbol: 'none',
                        lineStyle: { width: 2, color: color || c.primary },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: (color || c.primary) + '66' },
                                { offset: 1, color: (color || c.primary) + '00' },
                            ]),
                        },
                    }],
                };
            };
        }

        document.addEventListener('DOMContentLoaded', function () {

            /* Sparklines */
            register(document.getElementById('spark-revenue'), sparkOpt([12,18,15,22,28,24,32,30,38]));
            register(document.getElementById('spark-users'),   sparkOpt([8,11,9,14,12,18,16,22,20],  colors().secondary));
            register(document.getElementById('spark-orders'),  sparkOpt([22,18,24,20,28,26,32,30,36], colors().success));
            register(document.getElementById('spark-conv'),    sparkOpt([3,4,3,5,6,5,7,6,8],          colors().warning));

            /* Revenue area chart */
            register(document.getElementById('chart-revenue'), function (c) {
                return {
                    tooltip: { trigger: 'axis', backgroundColor: c.surface, borderColor: c.grid, textStyle: { color: c.text } },
                    legend:  { textStyle: { color: c.muted }, top: 0 },
                    grid:    { left: 30, right: 20, top: 40, bottom: 30 },
                    xAxis:   { type: 'category', data: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], ...baseAxis(c) },
                    yAxis:   { type: 'value', ...baseAxis(c) },
                    series: [
                        {
                            name: 'Revenue', type: 'line', smooth: true, symbol: 'circle', symbolSize: 6,
                            data: [12000,15000,14000,19000,22000,26000,28000,31000,29000,34000,38000,42000],
                            lineStyle: { width: 3, color: c.primary },
                            itemStyle: { color: c.primary },
                            areaStyle: {
                                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                    { offset: 0, color: c.primary + '55' },
                                    { offset: 1, color: c.primary + '00' },
                                ]),
                            },
                        },
                        {
                            name: 'Profit', type: 'line', smooth: true, symbol: 'circle', symbolSize: 6,
                            data: [4000,5500,5100,7000,8200,9500,10800,12100,11500,13200,15000,16800],
                            lineStyle: { width: 3, color: c.secondary },
                            itemStyle: { color: c.secondary },
                            areaStyle: {
                                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                    { offset: 0, color: c.secondary + '55' },
                                    { offset: 1, color: c.secondary + '00' },
                                ]),
                            },
                        },
                    ],
                };
            });

            /* Traffic donut chart */
            register(document.getElementById('chart-traffic'), function (c) {
                return {
                    tooltip: { trigger: 'item', backgroundColor: c.surface, borderColor: c.grid, textStyle: { color: c.text } },
                    legend:  { bottom: 0, textStyle: { color: c.muted } },
                    series: [{
                        type: 'pie', radius: ['55%', '78%'], avoidLabelOverlap: true,
                        itemStyle: { borderColor: c.surface, borderWidth: 3 },
                        label:     { show: false },
                        labelLine: { show: false },
                        data: [
                            { value: 1048, name: 'Direct',   itemStyle: { color: c.primary } },
                            { value: 735,  name: 'Search',   itemStyle: { color: c.secondary } },
                            { value: 580,  name: 'Email',    itemStyle: { color: c.info } },
                            { value: 484,  name: 'Social',   itemStyle: { color: c.warning } },
                            { value: 300,  name: 'Referral', itemStyle: { color: c.success } },
                        ],
                    }],
                };
            });

        }); // DOMContentLoaded
    })();
    </script>

    @endif {{-- end empty $content_page fallback --}}

@endsection
