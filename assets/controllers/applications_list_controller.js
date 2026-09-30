import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['search', 'status', 'job', 'body'];

    connect() {
        this.debounceTimer = null;
        this.load();
    }

    onSearchInput() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => this.load(), 250);
    }

    load() {
        const params = new URLSearchParams();
        if (this.searchTarget.value.trim() !== '') params.set('search', this.searchTarget.value.trim());
        if (this.statusTarget.value !== '') params.set('status', this.statusTarget.value);
        if (this.jobTarget.value !== '') params.set('jobId', this.jobTarget.value);

        fetch('/api/applications?' + params.toString())
            .then((response) => response.json())
            .then((applications) => this.render(applications))
            .catch(() => {
                this.bodyTarget.innerHTML = '<tr><td colspan="7" class="empty-state">Error al cargar las candidaturas.</td></tr>';
            });
    }

    render(applications) {
        this.bodyTarget.textContent = '';

        if (applications.length === 0) {
            const emptyRow = document.createElement('tr');
            const emptyCell = document.createElement('td');
            emptyCell.colSpan = 7;
            emptyCell.className = 'empty-state';
            emptyCell.textContent = 'No se han encontrado candidaturas.';
            emptyRow.appendChild(emptyCell);
            this.bodyTarget.appendChild(emptyRow);
            return;
        }

        applications.forEach((item) => {
            const row = document.createElement('tr');

            const nameCell = document.createElement('td');
            const nameLink = document.createElement('a');
            nameLink.className = 'row-link';
            nameLink.href = '/applications/' + encodeURIComponent(item.id);
            nameLink.textContent = item.fullName;
            nameCell.appendChild(nameLink);

            const jobTitleCell = document.createElement('td');
            jobTitleCell.textContent = item.jobTitle;

            const emailCell = document.createElement('td');
            emailCell.textContent = item.email;

            const statusCell = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = 'badge badge-' + item.status;
            badge.textContent = this.statusLabel(item.status);
            statusCell.appendChild(badge);

            const scoreCell = document.createElement('td');
            scoreCell.className = 'score';
            scoreCell.textContent = item.aiScore === null ? '—' : item.aiScore;

            const dateCell = document.createElement('td');
            dateCell.textContent = new Date(item.appliedAt).toLocaleString();

            const actionsCell = document.createElement('td');
            const viewButton = document.createElement('a');
            viewButton.className = 'btn';
            viewButton.href = '/applications/' + encodeURIComponent(item.id);
            viewButton.textContent = 'Ver';
            actionsCell.appendChild(viewButton);

            row.append(nameCell, jobTitleCell, emailCell, statusCell, scoreCell, dateCell, actionsCell);
            this.bodyTarget.appendChild(row);
        });
    }

    statusLabel(value) {
        return value === 'enriched' ? 'Enriquecida' : 'Recibida';
    }
}
