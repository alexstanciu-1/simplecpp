/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2015-2021 Derick Rethans
 * Copyright (c) 2021 MongoDB
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

#include "timelib.h"
#include "timelib_private.h"

void timelib_update_from_sse(timelib_time *tm)
{
	timelib_sll sse;
	int z = tm->z;
	signed int dst = tm->dst;

	sse = tm->sse;

	switch (tm->zone_type) {
		case TIMELIB_ZONETYPE_ABBR:
		case TIMELIB_ZONETYPE_OFFSET: {
			timelib_unixtime2gmt(tm, tm->sse + tm->z + (tm->dst * 3600));

			goto cleanup;
		}

		case TIMELIB_ZONETYPE_ID: {
			int32_t  offset = 0;

			timelib_get_time_zone_offset_info(tm->sse, tm->tz_info, &offset, NULL, NULL);
			timelib_unixtime2gmt(tm, tm->sse + offset);

			goto cleanup;
		}

		default:
			timelib_unixtime2gmt(tm, tm->sse);
			goto cleanup;
	}
cleanup:
	tm->sse = sse;
	tm->is_localtime = 1;
	tm->have_zone = 1;
	tm->z = z;
	tm->dst = dst;
}

void timelib_unixtime2local(timelib_time *tm, timelib_sll ts)
{
	timelib_time_offset *gmt_offset;
	timelib_tzinfo      *tz = tm->tz_info;

	switch (tm->zone_type) {
		case TIMELIB_ZONETYPE_ABBR:
		case TIMELIB_ZONETYPE_OFFSET: {
			int z = tm->z;
			signed int dst = tm->dst;

			timelib_unixtime2gmt(tm, ts + tm->z + (tm->dst * 3600));

			tm->sse = ts;
			tm->z = z;
			tm->dst = dst;
			break;
		}

		case TIMELIB_ZONETYPE_ID:
			gmt_offset = timelib_get_time_zone_info(ts, tz);
			timelib_unixtime2gmt(tm, ts + gmt_offset->offset);

			/* we need to reset the sse here as unixtime2gmt modifies it */
			tm->sse = ts;
			tm->dst = gmt_offset->is_dst;
			tm->z = gmt_offset->offset;
			tm->tz_info = tz;

			timelib_time_tz_abbr_update(tm, gmt_offset->abbr);
			timelib_time_offset_dtor(gmt_offset);
			break;

		default:
			tm->is_localtime = 0;
			tm->have_zone = 0;
			return;
	}

	tm->is_localtime = 1;
	tm->have_zone = 1;
}

void timelib_set_timezone_from_offset(timelib_time *t, timelib_sll utc_offset)
{
	if (t->tz_abbr) {
		timelib_free(t->tz_abbr);
	}
	t->tz_abbr = NULL;

	t->z = utc_offset;
	t->have_zone = 1;
	t->zone_type = TIMELIB_ZONETYPE_OFFSET;
	t->dst = 0;
	t->tz_info = NULL;
}

void timelib_set_timezone_from_abbr(timelib_time *t, timelib_abbr_info abbr_info)
{
	if (t->tz_abbr) {
		timelib_free(t->tz_abbr);
	}
	t->tz_abbr = timelib_strdup(abbr_info.abbr);

	t->z = abbr_info.utc_offset;
	t->have_zone = 1;
	t->zone_type = TIMELIB_ZONETYPE_ABBR;
	t->dst = abbr_info.dst;
	t->tz_info = NULL;
}

void timelib_set_timezone(timelib_time *t, timelib_tzinfo *tz)
{
	timelib_time_offset *gmt_offset;

	gmt_offset = timelib_get_time_zone_info(t->sse, tz);
	t->z = gmt_offset->offset;
/*
	if (t->dst != gmt_offset->is_dst) {
		printf("ERROR (%d, %d)\n", t->dst, gmt_offset->is_dst);
		exit(1);
	}
*/
	t->dst = gmt_offset->is_dst;
	t->tz_info = tz;
	if (t->tz_abbr) {
		timelib_free(t->tz_abbr);
	}
	t->tz_abbr = timelib_strdup(gmt_offset->abbr);
	timelib_time_offset_dtor(gmt_offset);

	t->have_zone = 1;
	t->zone_type = TIMELIB_ZONETYPE_ID;
}

/* Converts the time stored in the struct to localtime if localtime = true,
 * otherwise it converts it to gmttime. This is only done when necessary
 * of course. */
int timelib_apply_localtime(timelib_time *t, unsigned int localtime)
{
	if (localtime) {
		/* Converting from GMT time to local time */
		TIMELIB_DEBUG(printf("Converting from GMT time to local time\n"););

		/* Check if TZ is set */
		if (!t->tz_info) {
			TIMELIB_DEBUG(printf("E: No timezone configured, can't switch to local time\n"););
			return -1;
		}

		timelib_unixtime2local(t, t->sse);
	} else {
		/* Converting from local time to GMT time */
		TIMELIB_DEBUG(printf("Converting from local time to GMT time\n"););

		timelib_unixtime2gmt(t, t->sse);
	}
	return 0;
}
