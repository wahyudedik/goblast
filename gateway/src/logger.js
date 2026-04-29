const pino = require('pino');

const isDev = process.env.NODE_ENV !== 'production';

function createLogger(name) {
    return pino(
        {
            name,
            level: process.env.LOG_LEVEL || 'info',
        },
        isDev
            ? pino.transport({
                target: 'pino-pretty',
                options: {
                    colorize: true,
                    translateTime: 'SYS:standard',
                    ignore: 'pid,hostname',
                },
            })
            : process.stdout
    );
}

module.exports = { createLogger };
