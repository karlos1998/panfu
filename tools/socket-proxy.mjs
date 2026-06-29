import crypto from "node:crypto";
import http from "node:http";
import net from "node:net";

const wsHost = process.env.WS_HOST || "0.0.0.0";
const wsPort = Number(process.env.WS_PORT || 9596);
const tcpHost = process.env.TCP_HOST || "127.0.0.1";
const tcpPort = Number(process.env.TCP_PORT || 9595);

function writeFrame(socket, opcode, payload = Buffer.alloc(0)) {
    const data = Buffer.isBuffer(payload) ? payload : Buffer.from(payload);
    let header;

    if (data.length < 126) {
        header = Buffer.from([0x80 | opcode, data.length]);
    } else if (data.length <= 0xffff) {
        header = Buffer.alloc(4);
        header[0] = 0x80 | opcode;
        header[1] = 126;
        header.writeUInt16BE(data.length, 2);
    } else {
        header = Buffer.alloc(10);
        header[0] = 0x80 | opcode;
        header[1] = 127;
        header.writeBigUInt64BE(BigInt(data.length), 2);
    }

    socket.write(Buffer.concat([header, data]));
}

function readFrames(buffer, onFrame) {
    let offset = 0;

    while (buffer.length - offset >= 2) {
        const first = buffer[offset];
        const second = buffer[offset + 1];
        const opcode = first & 0x0f;
        const masked = (second & 0x80) !== 0;
        let length = second & 0x7f;
        let headerLength = 2;

        if (length === 126) {
            if (buffer.length - offset < 4) {
                break;
            }
            length = buffer.readUInt16BE(offset + 2);
            headerLength = 4;
        } else if (length === 127) {
            if (buffer.length - offset < 10) {
                break;
            }
            const bigLength = buffer.readBigUInt64BE(offset + 2);
            if (bigLength > BigInt(Number.MAX_SAFE_INTEGER)) {
                throw new Error("WebSocket frame is too large");
            }
            length = Number(bigLength);
            headerLength = 10;
        }

        const maskLength = masked ? 4 : 0;
        const frameLength = headerLength + maskLength + length;
        if (buffer.length - offset < frameLength) {
            break;
        }

        const maskOffset = offset + headerLength;
        const payloadOffset = maskOffset + maskLength;
        const payload = Buffer.from(buffer.subarray(payloadOffset, payloadOffset + length));

        if (masked) {
            const mask = buffer.subarray(maskOffset, maskOffset + 4);
            for (let i = 0; i < payload.length; i += 1) {
                payload[i] ^= mask[i % 4];
            }
        }

        onFrame(opcode, payload);
        offset += frameLength;
    }

    return buffer.subarray(offset);
}

const server = http.createServer();

server.on("upgrade", (request, browserSocket) => {
    const key = request.headers["sec-websocket-key"];
    if (!key) {
        browserSocket.destroy();
        return;
    }

    const accept = crypto
        .createHash("sha1")
        .update(`${key}258EAFA5-E914-47DA-95CA-C5AB0DC85B11`)
        .digest("base64");

    browserSocket.write([
        "HTTP/1.1 101 Switching Protocols",
        "Upgrade: websocket",
        "Connection: Upgrade",
        `Sec-WebSocket-Accept: ${accept}`,
        "",
        "",
    ].join("\r\n"));

    const tcpSocket = net.connect({ host: tcpHost, port: tcpPort });
    let browserBuffer = Buffer.alloc(0);

    browserSocket.on("data", (chunk) => {
        try {
            browserBuffer = readFrames(Buffer.concat([browserBuffer, chunk]), (opcode, payload) => {
                if (opcode === 0x8) {
                    tcpSocket.end();
                    writeFrame(browserSocket, 0x8);
                    browserSocket.end();
                } else if (opcode === 0x9) {
                    writeFrame(browserSocket, 0xa, payload);
                } else if (opcode === 0x1 || opcode === 0x2 || opcode === 0x0) {
                    tcpSocket.write(payload);
                }
            });
        } catch (error) {
            console.error(error);
            browserSocket.destroy();
            tcpSocket.destroy();
        }
    });

    tcpSocket.on("data", (chunk) => writeFrame(browserSocket, 0x2, chunk));
    tcpSocket.on("error", () => browserSocket.destroy());
    tcpSocket.on("close", () => {
        if (!browserSocket.destroyed) {
            writeFrame(browserSocket, 0x8);
            browserSocket.end();
        }
    });

    browserSocket.on("error", () => tcpSocket.destroy());
    browserSocket.on("close", () => tcpSocket.destroy());
});

server.listen(wsPort, wsHost, () => {
    console.log(`Socket proxy listening on ws://${wsHost}:${wsPort} -> ${tcpHost}:${tcpPort}`);
});
