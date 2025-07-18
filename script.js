// script.js
document.addEventListener('DOMContentLoaded', function() {
    // Função para atualizar a hora
    function updateTime() {
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false // Formato 24h
            };
            // Define o idioma para Português do Brasil e o fuso horário
            timeElement.textContent = now.toLocaleString('pt-BR', { ...options, timeZone: 'America/Sao_Paulo' });
        }
    }

    // Atualiza a hora a cada segundo
    updateTime();
    setInterval(updateTime, 1000);

    // Adicione aqui qualquer outra lógica JavaScript para interatividade

    // Exemplo: Animação ao passar o mouse em cards (apenas CSS já faz isso, mas JS seria para algo mais complexo)
    // const cards = document.querySelectorAll('.card');
    // cards.forEach(card => {
    //     card.addEventListener('mouseenter', () => {
    //         card.style.transform = 'translateY(-5px) scale(1.01)';
    //         card.style.boxShadow = '0 8px 30px var(--shadow-medium)';
    //     });
    //     card.addEventListener('mouseleave', () => {
    //         card.style.transform = 'translateY(0) scale(1)';
    //         card.style.boxShadow = '0 4px 20px var(--shadow-light)';
    //     });
    // });
});