@section('title', $title)
<div>
@include('components.layouts.sidenav')
<div id="main">
    @include('components.layouts.header')
    <div class="content recipe-page">
        <div class="col-12">
            <div class="recipe">
                <h1>{{$this->recipe->name}}</h1>
    
                <div class="actions">
                    <ul>
                        <li data-toggle="tooltip" data-placement="left" title="" data-original-title="Favorite">
                            <button wire:click="toggleFavourite" name="favorite" class="btn btn-none">
                                <i class="fa fa-star @if($this->recipe->favourite) favorite @endif"></i>
                            </button>
                        </li>
                        {{-- <li data-toggle="tooltip" data-placement="left" title="" data-original-title="Share">
                            <button type="button" class="btn btn-none" data-toggle="modal" data-target="#share">
                                <i class="fa fa-share-alt"></i>
                            </button>
                        </li>
                        <li data-toggle="tooltip" data-placement="left" title="" data-original-title="Print">
                            <button type="button" class="btn btn-none" data-toggle="modal" data-target="#print">
                                <i class="fa fa-print"></i>
                            </button>
                        </li>
                        <li data-toggle="tooltip" data-placement="left" title="" data-original-title="Gallery">
                            <button type="button" class="btn btn-none" data-toggle="modal" data-target="#gallery">
                                <i class="fa fa-photo"></i>
                            </button>
                        </li> --}}
                        <li data-toggle="tooltip" data-placement="left" title="" data-original-title="Edit">
                            <a href="{{$this->recipe->id}}/edit" class="btn btn-none">
                                <i class="fa fa-edit"></i>
                            </a>
                        </li>
                        <li data-toggle="tooltip" data-placement="left" title="" data-original-title="Delete">
                            <button type="button" class="btn btn-none" data-toggle="modal" data-target="#delete">
                                <i class="fa fa-trash"></i>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="recipe-list">
                    <ul>
                        @foreach ($this->recipe->ingredients as $ingredient)
                            @if (str_starts_with($ingredient->name, '#'))
                                <li><b>{{substr($ingredient->name, 1)}}</b></li>
                            @else
                                <li>{{$ingredient->name}}</li>
                            @endif
                        @endforeach
                </div>
                <div class="description">
                    {!! nl2br(e($this->recipe->description)) !!}
                </div>
                <div class="tags">
                    <label>Tags:</label>
                    <span>
                        @foreach($this->recipe->tags as $index => $tag)
                            <a href="/tag/{{$tag->name}}">{{$tag->name}}</a>@if($index < count($this->recipe->tags) - 1), @endif
                        @endforeach
                    </span>
                    <div class="clear"></div>
                </div>
                <div class="tags">
                    <label>Categories:</label>
                    <span>
                        @foreach ($this->recipe->categories as $index => $category)
                            <a href="/category/{{$category->id}}-{{$category->name}}">{{$category->name}}</a>@if($index < count($this->recipe->categories) - 1), @endif
                        @endforeach
                    </span>
                    <div class="clear"></div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    
        @if (!empty($this->recipe->note))
            <div class="col-12">
                <div class="notes">
                    <h1>Notes</h1>
                    <div class="notes-text">
                        {!! nl2br(e($this->recipe->note)) !!}
                        <div class="clear"></div>
                    </div>
                </div>
            </div>
        @endif
         
        <div class="modal fade" id="share" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                        <h4 class="modal-title">Share: Babygrød
                        </h4>
                    </div>
                    <div class="modal-body">
                        <h3 style="margin-top:0;" class="float-left">Sharing links</h3>
                        <button class="btn btn-success btn-xs float-right" id="addLink">Add link</button>
    
                        <table class="table table-stripped links">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                                        </tbody>
                        </table>
    
                        <div style="clear:both;"></div>
    
                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="clear"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="modal fade" id="print" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                        <h4 class="modal-title">Print: Babygrød
                        </h4>
                    </div>
                    <div class="modal-body">
                        <div class="col-6">
                            <div class="form-group">
                                <button onclick="Print(1)" class="full-width btn btn-default" type="button">
                                    <i class="fa fa-print"></i>
                                    Print Without Notes</button>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <button onclick="Print(2)" class="full-width btn btn-default" type="button">
                                    <i class="fa fa-print"></i>
                                    Print With Notes</button>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="modal fade" id="gallery" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                        <h4 class="modal-title">Add image to Babygrød
                        </h4>
                    </div>
                    <form method="post" id="form" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="edit-recipe">
                                <div class="col-12">
                                    <div class="form-group">
                                        <input type="file" name="image" class="form-control">
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <div class="clear"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <input type="submit" name="upload" class="noEnterSubmit btn btn-primary" value="Upload">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    
        <div class="modal fade" id="delete" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                        <h4 class="modal-title">Delete: Babygrød
                        </h4>
                    </div>
                    <form method="post" wire:submit.prevent="delete">
                        <div class="modal-body">
                            <div class="remove-recipe">
                                <div class="col-12">
                                    <div class="form-group">
                                        <input disabled="disabled" type="text" class="form-control" value="Babygrød">
                                    </div>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="check" wire:model="deleteCheck" value="1">Are you sure to delete this? This cannot be undone!</label>
                                </div>
                                <div class="clear"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <input type="submit" name="delete" class="noEnterSubmit btn btn-danger" value="Delete">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    
        <div class="modal fade" id="image" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                        <h4 class="modal-title">Image</h4>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <div class="remove-recipe">
                                <input type="number" name="imageId" class="id" style="display:none;">
                                <div class="col-12"></div>
                                <div class="clear"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <input type="submit" name="deleteImage" onclick="return confirm('Are you sure to delete this? This cannot be undone!');" class="noEnterSubmit btn btn-danger" value="Delete">
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>
        <script>
            $(document).on('click', '#addLink', function () {
                if (!confirm('Are you sure?')) {
                    return false;
                }
                $.post("?addLink", {
    
                }, function (data) {
                    var html;
                    if (data != "") {
                        data = JSON.parse(data);
                        html = '';
                        html += '<tr data-code="' + data.code + '">';
                        html += '    <td><a href="' + data.full +
                            '" target="_blank" style="font-size:1rem;">' + data.code +
                            '</a><input class="copy" value="' + data.full + '"></td>';
                        html += '    <td class="align-right">';
                        html += '        <button class="shareCopy btn btn-primary btn-sm">';
                        html += '            <i class="fa fa-copy"></i>';
                        html += '        </button>';
                        html += '        <button class="shareDelete btn btn-danger btn-sm">';
                        html += '            <i class="fa fa-trash"></i>';
                        html += '        </button>';
                        html += '    </td>';
                        html += '</tr>';
                        $('.links tbody').append(html);
                    } else {
                        alert('ERROR');
                    }
                });
            });
            $(document).on('click', '.shareDelete', function () {
                if (!confirm('Are you sure?')) {
                    return false;
                }
                var code = $(this).parent().parent().attr('data-code');
                var tr = $(this).parent().parent();
                $.post("?deleteLink", {
                    code: code
                }, function (data) {
                    if (data != "") {
                        tr.remove();
                    } else {
                        alert('ERROR');
                    }
                });
            });
            $(document).on('click', '.shareCopy', function () {
                var text = $(this).parent().parent().find('.copy');
                text.select();
                document.execCommand("Copy");
            });
    
            function openImage(url, id) {
                $("#image .id").val(id);
                $("#image .col-12").html("<img style=\"width:100%;\" src=\"" + url + "\">");
                $('#image').modal();
            }
    
            /**
             * @return {boolean}
             */
            function Print(type) {
                var left = (screen.width / 2) - (600 / 2);
                var mywindow = window.open('/recipe/print/{{$this->recipe->id}}/' + type, null, 'height=400,width=600,left=' + left);
                // mywindow.document.close();  necessary for IE >= 10
                mywindow.focus(); // necessary for IE >= 10
                mywindow.print();
                // mywindow.close();
                return true;
            }
            $(document).ready(function () {
                $('.noEnterSubmit').keypress(function (e) {
                    if (e.which == 13)
                        e.preventDefault();
                });
    
                $('[data-toggle="popover"]').popover({
                    html: true
                });
            });
        </script>
            <script>
            $(function () {
                $('[data-toggle="tooltip"]').tooltip()
            })
        </script>
        <div class="alert alert-info" role="alert"><header>Information</header><main><span class="alert_text"></span></main></div>
        <div class="alert alert-success" role="alert"><header>Success</header><main><span class="alert_text"></span></main></div>
        <div class="alert alert-warning" role="alert"><header>Warning</header><main><span class="alert_text"></span></main></div>
        <div class="alert alert-danger" role="alert"><header>Error</header><main><span class="alert_text"></span></main></div>
    </div>
</div>