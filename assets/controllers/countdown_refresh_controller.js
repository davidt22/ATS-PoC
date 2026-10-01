import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['counter'];
    static values = { seconds: { type: Number, default: 5 } };

    connect() {
        this.remaining = this.secondsValue;
        this.updateCounter();
        this.timer = setInterval(() => this.tick(), 1000);
    }

    disconnect() {
        clearInterval(this.timer);
    }

    tick() {
        this.remaining -= 1;
        this.updateCounter();

        if (this.remaining <= 0) {
            clearInterval(this.timer);
            window.location.reload();
        }
    }

    updateCounter() {
        if (this.hasCounterTarget) {
            this.counterTarget.textContent = this.remaining;
        }
    }
}
