import Alpine from 'alpinejs';

// The public assessment page has no Livewire, so nothing else starts Alpine.
// (resources/js/app.js deliberately leaves the start to Livewire; this entry
// must NOT import app.js or it will pull in Chart.js and the staff bundle.)
window.Alpine = Alpine;

Alpine.start();
