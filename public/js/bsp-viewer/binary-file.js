/* 
 * binFile.js - Binary Stream Reader
 * version 1.0
 */
 
/*
 * Copyright (c) 2011 Brandon Jones
 *
 * This software is provided 'as-is', without any express or implied
 * warranty. In no event will the authors be held liable for any damages
 * arising from the use of this software.
 *
 * Permission is granted to anyone to use this software for any purpose,
 * including commercial applications, and to alter it and redistribute it
 * freely, subject to the following restrictions:
 *
 *    1. The origin of this software must not be misrepresented; you must not
 *    claim that you wrote the original software. If you use this software
 *    in a product, an acknowledgment in the product documentation would be
 *    appreciated but is not required.
 *
 *    2. Altered source versions must be plainly marked as such, and must not
 *    be misrepresented as being the original software.
 *
 *    3. This notice may not be removed or altered from any source
 *    distribution.
 */

BinaryFile = function(data) {
    if (data instanceof ArrayBuffer) {
        this.u8 = new Uint8Array(data);
        this.dv = new DataView(data);
        this.length = data.byteLength;
        this.useArrayBuffer = true;
    } else {
        this.buffer = data;
        this.length = data.length;
        this.useArrayBuffer = false;
    }
    this.offset = 0;
};
// This is the result of an interesting trick that Google does in their
// GWT port of Quake 2. (For floats, anyway...) Rather than parse and 
// calculate the values manually they share the contents of a byte array
// between several types of buffers, which allows you to push into one and
// read out the other. The end result is, effectively, a typecast!

var bf_byteBuff = new ArrayBuffer(4);

var bf_wba = new Int8Array(bf_byteBuff);
var bf_wuba = new Uint8Array(bf_byteBuff);

var bf_wsa = new Int16Array(bf_byteBuff);
var bf_wusa = new Uint16Array(bf_byteBuff);

var bf_wia = new Int32Array(bf_byteBuff);
var bf_wuia = new Uint32Array(bf_byteBuff);

var bf_wfa = new Float32Array(bf_byteBuff);

BinaryFile.prototype.eof = function() {
    this.offset >= this.length;
}

// Seek to the given byt offset within the stream
BinaryFile.prototype.seek = function(offest) {
    this.offset = offest;
};

// Seek to the given byt offset within the stream
BinaryFile.prototype.tell = function() {
    return this.offset;
};

// Read a signed byte from the stream
BinaryFile.prototype.readByte = function() {
    var val = this.useArrayBuffer ? (this.u8[this.offset] << 24 >> 24) : (this.buffer.charCodeAt(this.offset) & 0xff);
    this.offset += 1;
    return val;
}

// Read an unsigned byte from the stream
BinaryFile.prototype.readUByte = function() {
    var val = this.useArrayBuffer ? this.u8[this.offset] : (this.buffer.charCodeAt(this.offset) & 0xff);
    this.offset += 1;
    return val;
}

// Read a signed short (2 bytes) from the stream
BinaryFile.prototype.readShort = function() {
    if (this.useArrayBuffer) {
        var val = this.dv.getInt16(this.offset, true);
        this.offset += 2;
        return val;
    }
    var off = this.offset;
    var buf = this.buffer;
    bf_wuba[0] = buf.charCodeAt(off) & 0xff;
    bf_wuba[1] = buf.charCodeAt(off+1) & 0xff;
    this.offset += 2;
    return bf_wsa[0];
};

// Read an unsigned short (2 bytes) from the stream
BinaryFile.prototype.readUShort = function() {
    if (this.useArrayBuffer) {
        var val = this.dv.getUint16(this.offset, true);
        this.offset += 2;
        return val;
    }
    var off = this.offset;
    var buf = this.buffer;
    bf_wuba[0] = buf.charCodeAt(off) & 0xff;
    bf_wuba[1] = buf.charCodeAt(off+1) & 0xff;
    this.offset += 2;
    return bf_wusa[0];
};

// Read a signed long (4 bytes) from the stream
BinaryFile.prototype.readLong = function() {
    if (this.useArrayBuffer) {
        var val = this.dv.getInt32(this.offset, true);
        this.offset += 4;
        return val;
    }
    var off = this.offset;
    var buf = this.buffer;
    bf_wuba[0] = buf.charCodeAt(off) & 0xff;
    bf_wuba[1] = buf.charCodeAt(off+1) & 0xff;
    bf_wuba[2] = buf.charCodeAt(off+2) & 0xff;
    bf_wuba[3] = buf.charCodeAt(off+3) & 0xff;
    this.offset += 4;
    return bf_wia[0];
};

// Read an unsigned long (4 bytes) from the stream
BinaryFile.prototype.readULong = function() {
    if (this.useArrayBuffer) {
        var val = this.dv.getUint32(this.offset, true);
        this.offset += 4;
        return val;
    }
    var off = this.offset;
    var buf = this.buffer;
    bf_wuba[0] = buf.charCodeAt(off) & 0xff;
    bf_wuba[1] = buf.charCodeAt(off+1) & 0xff;
    bf_wuba[2] = buf.charCodeAt(off+2) & 0xff;
    bf_wuba[3] = buf.charCodeAt(off+3) & 0xff;
    this.offset += 4;
    return bf_wuia[0];
};

// Read a float (4 bytes) from the stream
BinaryFile.prototype.readFloat = function() {
    if (this.useArrayBuffer) {
        var val = this.dv.getFloat32(this.offset, true);
        this.offset += 4;
        return val;
    }
    var off = this.offset;
    var buf = this.buffer;
    bf_wuba[0] = buf.charCodeAt(off) & 0xff;
    bf_wuba[1] = buf.charCodeAt(off+1) & 0xff;
    bf_wuba[2] = buf.charCodeAt(off+2) & 0xff;
    bf_wuba[3] = buf.charCodeAt(off+3) & 0xff;
    this.offset += 4;
    return bf_wfa[0];
};

BinaryFile.prototype.expandHalf = function(h) {
    var s = (h & 0x8000) >> 15;
    var e = (h & 0x7C00) >> 10;
    var f = h & 0x03FF;
    
    if(e == 0) {
        return (s?-1:1) * Math.pow(2,-14) * (f/Math.pow(2, 10));
    } else if (e == 0x1F) {
        return f?NaN:((s?-1:1)*Infinity);
    }
    
    return (s?-1:1) * Math.pow(2, e-15) * (1+(f/Math.pow(2, 10)));
};

BinaryFile.prototype.readHalf = function() {
    var h = this.readUShort();
    return this.expandHalf(h);
}

// Read an ASCII string of the given length from the stream
BinaryFile.prototype.readString = function(length) {
    if (this.useArrayBuffer) {
        var bytes = this.u8.subarray(this.offset, this.offset + length);
        var str = '';
        for (var i = 0; i < bytes.length; i++) {
            if (bytes[i] === 0) break;
            str += String.fromCharCode(bytes[i]);
        }
        this.offset += length;
        return str;
    }
    var str = this.buffer.substr(this.offset, length).replace(/\0+$/,'');
    this.offset += length;
    return str;
};
    
