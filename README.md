# what can this thing do

- Downloads your facebook, instagram or twitter feed and puts it somewhere of your choosing in your site tree
- Shares the current page to facebook or twitter when you save it (WIP).
- Improves the Page meta data with twitter, open graph and micro data
- Provides a number of new tokens to the page template for generating things like share urls


## meta data

Include the `Meta` partial in your page template e.g.:

````html
<head>

	<% base_tag %>
	<title>$Meta('Title')</title>

	<%-- meta data --%>
	<% include Meta %>
````


## todo

- DOCS!!!
- Cleanup the unnecessary manual management of twitter username and fb page url - these should be generated from the oauth data / page / user ids
- Need clear setup instructions for each social network
- Instagram push
- Common update behaviour should go into an extension
- Code Cleanup
- config.yml


License
-------

Copyright (c) 2015, azt3k
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
