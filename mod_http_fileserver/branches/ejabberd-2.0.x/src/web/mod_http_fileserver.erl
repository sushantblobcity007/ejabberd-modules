%%%----------------------------------------------------------------------
%%% File    : mod_http_fileserver.erl
%%% Author  : Massimiliano Mirra <mmirra [at] process-one [dot] net>
%%% Purpose : Simple file server plugin for embedded ejabberd web server
%%% Created :
%%%
%%%
%%% ejabberd, Copyright (C) 2002-2009   ProcessOne
%%%
%%% This program is free software; you can redistribute it and/or
%%% modify it under the terms of the GNU General Public License as
%%% published by the Free Software Foundation; either version 2 of the
%%% License, or (at your option) any later version.
%%%
%%% This program is distributed in the hope that it will be useful,
%%% but WITHOUT ANY WARRANTY; without even the implied warranty of
%%% MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
%%% General Public License for more details.
%%%
%%% You should have received a copy of the GNU General Public License
%%% along with this program; if not, write to the Free Software
%%% Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
%%% 02111-1307 USA
%%%
%%%----------------------------------------------------------------------

-module(mod_http_fileserver).
-author('mmirra@process-one.net').

-behaviour(gen_mod).

-export([
	 start/2,
	 stop/1,
	 process/2,
	 loop/1,
	 ctl_process/2
	]).

-include("ejabberd.hrl").
-include("jlib.hrl").
-include("web/ejabberd_http.hrl").
-include("ejabberd_ctl.hrl").
-include_lib("kernel/include/file.hrl").

-ifdef(SSL39).
-define(STRING2LOWER, string).
-else.
-define(STRING2LOWER, httpd_util).
-endif.

%%%----------------------------------------------------------------------
%%% REQUEST HANDLERS
%%%----------------------------------------------------------------------

%%-----------------------------------------------------------------------
%% FUNCTION
%%
%%   process/2
%%
%% PURPOSE
%%
%%   Handle an HTTP request.
%%
%% RETURNS
%%
%%   Page to be sent back to the client and/or HTTP status code.
%%
%% ARGUMENTS
%%
%% - LocalPath: part of the requested URL path that is "local to the
%%   module".
%%
%%-----------------------------------------------------------------------


process(LocalPath, Request) ->
    ?DEBUG("Requested ~p", [LocalPath]),

    Result = serve(LocalPath),
    case ets:lookup(mod_http_fileserver, accessfile) of
	[] ->
	    ok;
	[{accessfile, AccessFile}] ->
	    {Code, _, _} = Result,
	    log(AccessFile, Code, Request)
    end,
    Result.

serve(LocalPath) ->
    [{docroot, DocRoot}] = ets:lookup(mod_http_fileserver, docroot),
    FileName = filename:join(filename:split(DocRoot) ++ LocalPath),
    case file:read_file(FileName) of
        {ok, FileContents} ->
            ?DEBUG("Delivering content.", []),
            {200,
             [{"Server", "ejabberd"},
              {"Last-Modified", last_modified(FileName)},
              {"Content-Type", content_type(FileName)}],
             FileContents};
        {error, Error} ->
            ?DEBUG("Delivering error: ~p", [Error]),
            case Error of
                eacces -> {403, [], "Forbidden"};
                enoent -> {404, [], "Not found"};
                _Else -> {404, [], atom_to_list(Error)}
            end
    end.

ctl_process(_Val, ["reopen-weblog"]) ->
    mod_http_fileserver_server ! reopenlog,
    ?STATUS_SUCCESS;
ctl_process(Val, _Args) ->
	Val.

%%%----------------------------------------------------------------------
%%% UTILITIES
%%%----------------------------------------------------------------------

join([], _) ->
    "";
join([E], _) ->
    E;
join([H | T], Separator) ->
    lists:foldl(fun(E, Acc) -> lists:concat([Acc, Separator, E]) end, H, T).

log(File, Code, Request) ->
    {{Year, Month, Day}, {Hour, Minute, Second}} = calendar:local_time(),
    IP = join(tuple_to_list(element(1, Request#request.ip)), "."),
    Path = join(Request#request.path, "/"),
    Query = case join(lists:map(fun(E) -> lists:concat([element(1, E), "=", element(2, E)]) end,
				Request#request.q), "&") of
		[] ->
		    "";
		String ->
		    [$? | String]
	    end,
    % combined apache like log format :
    % 127.0.0.1 - - [28/Mar/2007:18:41:55 +0200] "GET / HTTP/1.1" 302 303 "-" "tsung"
    % XXX TODO some fields are harcoded/missing (reply size, user agent or referer for example)
    io:format(File, "~s - - [~p/~p/~p:~p:~p:~p] \"~s /~s~s\" ~p -1 \"-\" \"-\"~n",
	      [IP, Day, Month, Year, Hour, Minute, Second, Request#request.method, Path, Query, Code]).

content_type(Filename) ->
    case ?STRING2LOWER:to_lower(filename:extension(Filename)) of
        ".jpg"  -> "image/jpeg";
        ".jpeg" -> "image/jpeg";
        ".gif"  -> "image/gif";
        ".png"  -> "image/png";
        ".html" -> "text/html";
        ".css"  -> "text/css";
        ".txt"  -> "text/plain";
        ".xul"  -> "application/vnd.mozilla.xul+xml";
        ".jar"  -> "application/java-archive";
        ".xpi"  -> "application/x-xpinstall";
        ".js"   -> "application/x-javascript";
        _Else   -> "application/octet-stream"
    end.

last_modified(FileName) ->
    {ok, FileInfo} = file:read_file_info(FileName),
    Then = FileInfo#file_info.mtime,
    httpd_util:rfc1123_date(Then).

open_file(Filename) ->
    case file:open(Filename, [append]) of
	{ok, File} ->
	    ets:insert(mod_http_fileserver, {accessfile, File}),
	    ok;
	{error, _Reason} ->
	    {'EXIT', {unaccessible_accessfile, ?MODULE}}
    end.

loop(Filename) ->
    receive
	reopenlog ->
	    case ets:lookup(mod_http_fileserver, accessfile) of
		[] ->
		    ok;
		[{accessfile, AccessFile}] ->
		    file:close(AccessFile),
		    case open_file(Filename) of
			ok ->
			    ok;
			_ ->
			    error
		    end
	    end,
	    loop(Filename);
	stop ->
	    ok
    end.


%%%----------------------------------------------------------------------
%%% BEHAVIOUR CALLBACKS
%%%----------------------------------------------------------------------

%% TODO: Improve this module to allow each virtual host to have a different
%% options. See http://support.process-one.net/browse/EJAB-561
start(_Host, Opts) ->
    case ets:info(mod_http_fileserver, name) of
	undefined ->
	    start2(_Host, Opts);
	_ ->
	    ok
    end.

start2(_Host, Opts) ->
    case gen_mod:get_opt(docroot, Opts, undefined) of
        undefined ->
            {'EXIT', {missing_document_root, ?MODULE}};
        DocRoot ->
            case filelib:is_dir(DocRoot) of
                true ->
		    %% XXX WARNING, using a single ets table name will
		    %% not work with virtual hosts
                    ets:new(mod_http_fileserver, [named_table, public]),
                    ets:insert(mod_http_fileserver, [{docroot, DocRoot}]),
		    case gen_mod:get_opt(accesslog, Opts, undefined) of
			undefined ->
			    ok;
			Filename ->
			    %% XXX same remark as above for proc name
    			    ejabberd_ctl:register_commands(
			      [{"reopen-weblog",
				"reopen http fileserver log file"}],
			      ?MODULE, ctl_process),
			    register(mod_http_fileserver_server,
				     spawn(?MODULE, loop, [Filename])),
			    open_file(Filename)
		    end;
                _Else ->
                    {'EXIT', {unaccessible_document_root, ?MODULE}}
            end
    end.

stop(_Host) ->
    case ets:info(mod_http_fileserver, name) of
	undefined ->
	    ok;
	_ ->
	    case ets:lookup(mod_http_fileserver, accessfile) of
		[] ->
		    ok;
		[{accessfile, AccessFile}] ->
		    ejabberd_ctl:unregister_commands(
		      [{"reopen-weblog",
			"reopen http fileserver log file"}],
		      ?MODULE, ctl_process),
		    mod_http_fileserver_server ! stop,
		    file:close(AccessFile)
	    end,
	    ets:delete(mod_http_fileserver)
    end,
    ok.
