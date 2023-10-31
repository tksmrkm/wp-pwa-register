declare const WP_REGISTER_SERVICE_WORKER: {
    webroot: string;
    root: string;
    base64: string;
    debug: boolean;
    register: {
        useDialog: boolean;
        icon?: string;
        message?: string;
    };
    nonce: string;
}
