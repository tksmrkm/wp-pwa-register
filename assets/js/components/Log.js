export default class Log
{
    constructor(endpoint) {
        this.endpoint = endpoint;
    }

    /**
     * debug.logに投げる
     * @param FormData data
     */
    logging(data) {
        const body = this.createData(data);
        fetch(this.endpoint, {
            method: 'POST',
            body: body
        });
    }

    createData(data) {
        const body = new FormData();

        if (data instanceof FormData === false) {
            for (let i in data) {
                body.append(i, data[i]);
            }

            return body;
        }

        return data;
    }
}