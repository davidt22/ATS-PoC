import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['select', 'output'];
    static values = { descriptions: Object };

    connect() {
        this.update();
    }

    update() {
        const description = this.descriptionsValue[this.selectTarget.value] ?? '';
        this.outputTarget.textContent = description;
        this.outputTarget.hidden = description === '';
    }
}
