@extends('layouts.main')

@section('title')
    {{ __('Email Templates') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Template Name') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($emailTemplates as $template)
                                <tr>
                                    <td>
                                        <strong>{{ $template['display_name'] }}</strong>
                                    </td>
                                    <td>
                                        {{ $template['description'] }}
                                    </td>
                                    <td>
                                        @if($template['has_template'])
                                            <span class="badge bg-success">{{ __('Configured') }}</span>
                                        @else
                                            <span class="badge bg-warning">{{ __('Not Configured') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('settings.email-templates.edit', $template['name']) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> {{ __('Edit') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
