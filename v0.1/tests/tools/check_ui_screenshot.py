#!/usr/bin/env python3
import argparse
import struct
import sys
import zlib


def paeth(left, above, upper_left):
    p = left + above - upper_left
    pa = abs(p - left)
    pb = abs(p - above)
    pc = abs(p - upper_left)
    if pa <= pb and pa <= pc:
        return left
    if pb <= pc:
        return above
    return upper_left


def bytes_per_pixel(color_type, bit_depth):
    if color_type == 6:
        return 4
    if color_type == 2:
        return 3
    if color_type == 4:
        return 2
    return 1


def scanline_length(width, color_type, bit_depth):
    if color_type == 6:
        channels = 4
    elif color_type == 2:
        channels = 3
    elif color_type == 4:
        channels = 2
    else:
        channels = 1
    return (width * channels * bit_depth + 7) // 8


def unpack_indexed(row, width, bit_depth):
    if bit_depth == 8:
        return list(row[:width])
    mask = (1 << bit_depth) - 1
    values = []
    for byte in row:
        for shift in range(8 - bit_depth, -1, -bit_depth):
            values.append((byte >> shift) & mask)
            if len(values) == width:
                return values
    return values


def read_png(path):
    with open(path, "rb") as handle:
        data = handle.read()
    if not data.startswith(b"\x89PNG\r\n\x1a\n"):
        raise ValueError("not a PNG file")

    offset = 8
    width = 0
    height = 0
    bit_depth = 0
    color_type = 0
    palette = []
    idat = []

    while offset < len(data):
        if offset + 8 > len(data):
            raise ValueError("truncated PNG chunk header")
        length = struct.unpack(">I", data[offset:offset + 4])[0]
        chunk_type = data[offset + 4:offset + 8]
        chunk_data = data[offset + 8:offset + 8 + length]
        offset += 12 + length

        if chunk_type == b"IHDR":
            width, height, bit_depth, color_type, compression, filter_method, interlace = struct.unpack(">IIBBBBB", chunk_data)
            if compression != 0 or filter_method != 0 or interlace != 0:
                raise ValueError("unsupported PNG compression/filter/interlace mode")
        elif chunk_type == b"PLTE":
            palette = [tuple(chunk_data[i:i + 3]) for i in range(0, len(chunk_data), 3)]
        elif chunk_type == b"IDAT":
            idat.append(chunk_data)
        elif chunk_type == b"IEND":
            break

    if width <= 0 or height <= 0:
        raise ValueError("missing PNG IHDR")

    raw = zlib.decompress(b"".join(idat))
    stride = scanline_length(width, color_type, bit_depth)
    bpp = bytes_per_pixel(color_type, bit_depth)
    rows = []
    source_offset = 0
    previous = bytearray(stride)

    for _ in range(height):
        filter_type = raw[source_offset]
        source_offset += 1
        row = bytearray(raw[source_offset:source_offset + stride])
        source_offset += stride

        for index in range(stride):
            left = row[index - bpp] if index >= bpp else 0
            above = previous[index]
            upper_left = previous[index - bpp] if index >= bpp else 0
            if filter_type == 1:
                row[index] = (row[index] + left) & 0xff
            elif filter_type == 2:
                row[index] = (row[index] + above) & 0xff
            elif filter_type == 3:
                row[index] = (row[index] + ((left + above) // 2)) & 0xff
            elif filter_type == 4:
                row[index] = (row[index] + paeth(left, above, upper_left)) & 0xff
            elif filter_type != 0:
                raise ValueError(f"unsupported PNG row filter {filter_type}")

        rows.append(bytes(row))
        previous = row

    pixels = []
    for row in rows:
        if color_type == 3:
            for value in unpack_indexed(row, width, bit_depth):
                if value >= len(palette):
                    raise ValueError("indexed PNG refers to a missing palette entry")
                pixels.append(palette[value])
        elif color_type == 6:
            for index in range(0, len(row), 4):
                pixels.append(tuple(row[index:index + 3]))
        elif color_type == 2:
            for index in range(0, len(row), 3):
                pixels.append(tuple(row[index:index + 3]))
        elif color_type == 0 and bit_depth == 8:
            for value in row:
                pixels.append((value, value, value))
        else:
            raise ValueError(f"unsupported PNG color type {color_type} with bit depth {bit_depth}")

    return width, height, pixels


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("path")
    parser.add_argument("--min-width", type=int, required=True)
    parser.add_argument("--min-height", type=int, required=True)
    parser.add_argument("--min-dark-pixels", type=int, default=500)
    parser.add_argument("--min-light-pixels", type=int, default=500)
    args = parser.parse_args()

    width, height, pixels = read_png(args.path)
    if width < args.min_width or height < args.min_height:
        print(f"{args.path}: expected at least {args.min_width}x{args.min_height}, got {width}x{height}", file=sys.stderr)
        return 1

    dark_pixels = 0
    light_pixels = 0
    unique = set()
    for red, green, blue in pixels:
        unique.add((red, green, blue))
        luma = (red * 299 + green * 587 + blue * 114) // 1000
        if luma <= 80:
            dark_pixels += 1
        if luma >= 220:
            light_pixels += 1

    if dark_pixels < args.min_dark_pixels:
        print(f"{args.path}: expected at least {args.min_dark_pixels} dark pixels, got {dark_pixels}", file=sys.stderr)
        return 1
    if light_pixels < args.min_light_pixels:
        print(f"{args.path}: expected at least {args.min_light_pixels} light pixels, got {light_pixels}", file=sys.stderr)
        return 1
    if len(unique) < 2:
        print(f"{args.path}: expected more than one color, got {len(unique)}", file=sys.stderr)
        return 1

    print(f"PASS: {args.path} {width}x{height} dark={dark_pixels} light={light_pixels} colors={len(unique)}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
