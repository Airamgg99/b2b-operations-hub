import Swal from 'sweetalert2';

export const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    background: '#ffffff',
    color: '#0f172a',
    customClass: {
        popup: 'rounded-xl shadow-lg border border-slate-200',
    },
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    },
});

export const notifySuccess = (title) => {
    Toast.fire({
        icon: 'success',
        title,
        iconColor: '#10b981', // emerald-500
    });
};

export const notifyError = (title) => {
    Toast.fire({
        icon: 'error',
        title,
        iconColor: '#ef4444', // red-500
    });
};
