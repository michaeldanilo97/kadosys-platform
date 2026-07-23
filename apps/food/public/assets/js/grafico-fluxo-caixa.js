(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var canvas = document.querySelector('[data-grafico-fluxo-caixa]');

        if (!canvas || !window.Chart) {
            return;
        }

        var dados;

        try {
            dados = JSON.parse(canvas.getAttribute('data-dados') || '{}');
        } catch (erro) {
            return;
        }

        var corTexto = getComputedStyle(document.documentElement).getPropertyValue('--text').trim() || '#E5E7EB';
        var corGrade = getComputedStyle(document.documentElement).getPropertyValue('--glass-border').trim() || 'rgba(255,255,255,0.08)';

        new window.Chart(canvas, {
            type: 'line',
            data: {
                labels: dados.meses || [],
                datasets: [
                    {
                        label: 'Receitas',
                        data: dados.receitas || [],
                        borderColor: '#34D399',
                        backgroundColor: 'rgba(52, 211, 153, 0.15)',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Despesas',
                        data: dados.despesas || [],
                        borderColor: '#F87171',
                        backgroundColor: 'rgba(248, 113, 113, 0.12)',
                        tension: 0.3,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: corTexto } },
                },
                scales: {
                    x: { ticks: { color: corTexto }, grid: { color: corGrade } },
                    y: { ticks: { color: corTexto }, grid: { color: corGrade } },
                },
            },
        });
    });
})();
