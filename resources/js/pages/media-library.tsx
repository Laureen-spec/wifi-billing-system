import React, { useState, useEffect, useCallback, useRef, useMemo, memo } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { Upload, Search, X, Plus, Info, Copy, Download, MoreHorizontal, Image as ImageIcon, Calendar, HardDrive, BarChart3, Edit, Trash2, Folder, FolderOpen, Home, ArrowLeft } from 'lucide-react';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { downloadFile, formatDate, formatDateTime } from '@/utils/helpers';

interface MediaItem {
  id: number;
  name: string;
  file_name: string;
  url: string;
  thumb_url: string;
  size: number;
  mime_type: string;
  created_at: string;
}

export default function MediaLibraryDemo() {
  const { t } = useTranslation();
  const { props } = usePage();
  const csrfToken = (props as any).csrf_token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const [media, setMedia] = useState<MediaItem[]>([]);
  const [directories, setDirectories] = useState<any[]>([]);
  const [currentDirectory, setCurrentDirectory] = useState<number | null>(null);
  const [showAllFiles, setShowAllFiles] = useState(false);
  const [filteredMedia, setFilteredMedia] = useState<MediaItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [dragActive, setDragActive] = useState(false);
  const [showCreateDirectory, setShowCreateDirectory] = useState(false);
  const [newDirectoryName, setNewDirectoryName] = useState('');
  const [editingDirectory, setEditingDirectory] = useState<number | null>(null);
  const [editDirectoryName, setEditDirectoryName] = useState('');
  const [deleteDirectoryState, setDeleteDirectoryState] = useState<{isOpen: boolean; id: number | null}>({isOpen: false, id: null});

  const [infoModalOpen, setInfoModalOpen] = useState(false);
  const [selectedMediaInfo, setSelectedMediaInfo] = useState<MediaItem | null>(null);
  const itemsPerPage = 12;

  const fetchMedia = useCallback(async (showLoader = true) => {
    if (showLoader) setLoading(true);
    try {
      const params = new URLSearchParams();
      if (currentDirectory) {
        params.append('directory_id', currentDirectory.toString());
      }

      const response = await fetch(`${route('media.index')}?${params}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      const mediaArray = Array.isArray(data.media) ? data.media : Array.isArray(data) ? data : [];
      setMedia(mediaArray);
      setDirectories(data.directories || []);
      setFilteredMedia(mediaArray);
    } catch (error) {
      console.error('Failed to load media:', error);
      toast.error(t('Failed to load media'));
    } finally {
      if (showLoader) setLoading(false);
    }
  }, [currentDirectory]);

  useEffect(() => {
    const shouldShowLoader = media.length === 0;
    fetchMedia(shouldShowLoader);
  }, [fetchMedia]);

  const createDirectory = async () => {
    if (!newDirectoryName.trim()) {
      toast.error(t('Please enter a directory name'));
      return;
    }

    try {
      const response = await fetch(route('media.directories.create'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ name: newDirectoryName }),
      });

      const data = await response.json();

      if (response.ok) {
        toast.success(data.message || t('Directory created successfully'));
        setNewDirectoryName('');
        setShowCreateDirectory(false);
        fetchMedia(false);
      } else {
        toast.error(data.message || t('Failed to create directory'));
      }
    } catch (error) {
      console.error('Create directory error:', error);
      toast.error(t('Failed to create directory'));
    }
  };

  const updateDirectory = async () => {
    if (!editDirectoryName.trim()) {
      toast.error(t('Please enter a directory name'));
      return;
    }
    if (!editingDirectory) return;

    try {
      const response = await fetch(route('media.directories.update', editingDirectory), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ name: editDirectoryName }),
      });

      const data = await response.json();

      if (response.ok) {
        toast.success(data.message || t('Directory updated successfully'));
        setEditDirectoryName('');
        setEditingDirectory(null);
        fetchMedia(false);
      } else {
        toast.error(data.message || t('Failed to update directory'));
      }
    } catch (error) {
      console.error('Update directory error:', error);
      toast.error(t('Failed to update directory'));
    }
  };

  const deleteDirectory = async (id: number) => {
    try {
      const response = await fetch(route('media.directories.destroy', id), {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const data = await response.json();

      if (response.ok) {
        toast.success(data.message || t('Directory deleted successfully'));
        if (currentDirectory === id) {
          setCurrentDirectory(null);
        }
        fetchMedia(false);
      } else {
        toast.error(data.message || t('Failed to delete directory'));
      }
    } catch (error) {
      console.error('Delete directory error:', error);
      toast.error(t('Failed to delete directory'));
    }
    setDeleteDirectoryState({isOpen: false, id: null});
  };



  useEffect(() => {
    const mediaArray = Array.isArray(media) ? media : [];
    const filtered = mediaArray.filter(item =>
      item.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      item.file_name.toLowerCase().includes(searchTerm.toLowerCase())
    );
    setFilteredMedia(filtered);
    setCurrentPage(1);
  }, [searchTerm, media]);

  // Filter directories based on search
  const filteredDirectories = useMemo(() => {
    if (!searchTerm) return directories;
    return directories.filter((dir: any) => 
      dir.name.toLowerCase().includes(searchTerm.toLowerCase())
    );
  }, [searchTerm, directories]);



  const handleFileUpload = async (files: FileList) => {
    setUploading(true);

    const validFiles = Array.from(files);

    if (validFiles.length === 0) {
      setUploading(false);
      return;
    }

    const formData = new FormData();
    validFiles.forEach(file => {
      formData.append('files[]', file);
    });
    if (currentDirectory) {
      formData.append('directory_id', currentDirectory.toString());
    }

    try {
      const response = await fetch(route('media.batch'), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
        credentials: 'same-origin',
      });

      const result = await response.json();

      if (response.ok) {
        fetchMedia(false); // Refresh without loader
        toast.success(result.message);

        // Show individual errors if any
        if (result.errors && result.errors.length > 0) {
          result.errors.forEach((error: string) => {
            toast.error(error);
          });
        }
      } else {
        // Show individual errors if available, otherwise show main message
        if (result.errors && result.errors.length > 0) {
          result.errors.forEach((error: string) => {
            toast.error(error);
          });
        } else {
          toast.error(result.message || t('Failed to upload files'));
        }
      }
    } catch (error) {
      toast.error(t('Error uploading files'));
    }

    setUploading(false);
    setIsUploadModalOpen(false);
  };

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);

    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFileUpload(e.dataTransfer.files);
    }
  };

  const deleteMedia = async (id: number) => {
    try {
      const response = await fetch(route('media.destroy', id), {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      const data = await response.json();

      if (response.ok) {
        setMedia(prev => prev.filter(item => item.id !== id));
        toast.success(data.message || t('Media deleted successfully'));
      } else {
        toast.error(data.message || t('Failed to delete media'));
      }
    } catch (error) {
      console.error('Delete media error:', error);
      toast.error(t('Error deleting media'));
    }
  };

  const handleCopyLink = (url: string) => {
    navigator.clipboard.writeText(url);
    toast.success(t('File URL copied to clipboard'));
  };

  const handleDownload = (url: string) => {
    downloadFile(url);
  };

  const handleShowInfo = (item: MediaItem) => {
    setSelectedMediaInfo(item);
    setInfoModalOpen(true);
  };

  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = [t('Bytes'), t('KB'), t('MB'), t('GB')];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const getFileIcon = (mimeType: string) => {
    if (mimeType.startsWith('image/')) return <ImageIcon className="h-8 w-8 text-blue-500" />;
    if (mimeType.includes('pdf')) return <div className="h-12 w-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl text-white text-lg flex items-center justify-center font-bold shadow-lg">PDF</div>;
    if (mimeType.includes('word') || mimeType.includes('document')) return <div className="h-12 w-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl text-white text-lg flex items-center justify-center font-bold shadow-lg">DOC</div>;
    if (mimeType.includes('csv') || mimeType.includes('spreadsheet')) return <div className="h-12 w-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl text-white text-lg flex items-center justify-center font-bold shadow-lg">CSV</div>;
    if (mimeType.startsWith('video/')) return <div className="h-12 w-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl text-white text-lg flex items-center justify-center font-bold shadow-lg">VID</div>;
    if (mimeType.startsWith('audio/')) return <div className="h-12 w-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl text-white text-lg flex items-center justify-center font-bold shadow-lg">AUD</div>;
    return <div className="h-12 w-12 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl text-white text-sm flex items-center justify-center font-bold shadow-lg">FILE</div>;
  };

  const totalPages = Math.ceil(filteredMedia.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const currentMedia = filteredMedia.slice(startIndex, startIndex + itemsPerPage);
  const totalStorageUsed = useMemo(() => filteredMedia.reduce((acc, item) => acc + item.size, 0), [filteredMedia]);
  const imageCount = useMemo(() => filteredMedia.filter(item => item.mime_type.startsWith('image/')).length, [filteredMedia]);
  const folderCount = directories.length + 1;
  const isRootLibrary = currentDirectory === null && !showAllFiles;
  const activeDirectoryName = currentDirectory ? directories.find(d => d.id === currentDirectory)?.name : null;

  const allFilesFolder = useMemo(() => (
    <button
      type="button"
      key="all-files-folder"
      className="group flex min-h-[176px] flex-col justify-between rounded-3xl border border-slate-200 bg-white/90 p-5 text-left shadow-[0_18px_45px_rgba(15,23,42,0.06)] transition-all duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_24px_60px_rgba(15,23,42,0.10)]"
      onClick={() => {
        setCurrentDirectory(null);
        setShowAllFiles(true);
        setSearchTerm('');
      }}
    >
      <div className="flex items-start justify-between gap-3">
        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 transition-transform group-hover:scale-105">
          <FolderOpen className="h-6 w-6" />
        </div>
        <Badge variant="secondary" className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
          {t('Library')}
        </Badge>
      </div>
      <div className="space-y-2">
        <h3 className="truncate text-base font-bold text-slate-950" title="All Files">{t('All Files')}</h3>
        <p className="text-sm leading-6 text-slate-500">{t('Open every uploaded asset across folders.')}</p>
      </div>
    </button>
  ), [t]);

  const breadcrumbs = [
    { label: t('Media Library') }
  ];

  return (
    <AuthenticatedLayout
      breadcrumbs={breadcrumbs}
      pageTitle={t('Manage Media Library')}
      pageActions={
        <div className="flex gap-2">
          <Button
            variant="outline"
            onClick={() => setShowCreateDirectory(true)}
            className="rounded-xl border-slate-200 bg-white text-slate-700 shadow-sm hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800"
          >
            <Plus className="h-4 w-4 mr-2" />
            {t('New Folder')}
          </Button>
          <Button onClick={() => setIsUploadModalOpen(true)} className="rounded-xl bg-emerald-600 text-white shadow-sm hover:bg-emerald-700">
            <Upload className="h-4 w-4 mr-2" />
            {t('Upload Files')}
          </Button>
        </div>
      }
    >
      <Head title={t('Media Library')} />
      <div className="space-y-6">
        <section className="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.07)]">
          <div className="grid gap-6 p-6 lg:grid-cols-[1fr_360px]">
            <div className="space-y-6">
              <div className="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">
                <ImageIcon className="h-3.5 w-3.5" />
                {t('Media Vault')}
              </div>
              <div>
                <h2 className="text-2xl font-bold tracking-tight text-slate-950 md:text-3xl">{t('Organize every file from one clean workspace')}</h2>
                <p className="mt-2 max-w-2xl text-sm leading-7 text-slate-500">
                  {t('Manage uploads, folders, previews, links, and downloads without leaving the admin panel.')}
                </p>
              </div>
              <div className="flex flex-wrap items-center gap-2 text-sm">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => {
                    setCurrentDirectory(null);
                    setShowAllFiles(false);
                    setSearchTerm('');
                  }}
                  className="h-9 rounded-full border border-slate-200 bg-white px-3 text-slate-600 shadow-sm hover:bg-slate-50 hover:text-slate-950"
                >
                  <Home className="mr-2 h-4 w-4" />
                  {t('Media Library')}
                </Button>
                {(activeDirectoryName || showAllFiles) && (
                  <span className="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">
                    {activeDirectoryName || t('All Files')}
                  </span>
                )}
                {(currentDirectory !== null || showAllFiles) && (
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      setCurrentDirectory(null);
                      setShowAllFiles(false);
                      setSearchTerm('');
                    }}
                    className="h-9 rounded-full border-slate-200 bg-white px-3 text-slate-700 shadow-sm hover:bg-slate-50"
                  >
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    {t('Back')}
                  </Button>
                )}
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">{t('Files')}</span>
                  <ImageIcon className="h-4 w-4 text-emerald-700" />
                </div>
                <div className="mt-3 text-2xl font-bold text-slate-950">{filteredMedia.length}</div>
              </div>
              <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">{t('Images')}</span>
                  <BarChart3 className="h-4 w-4 text-sky-700" />
                </div>
                <div className="mt-3 text-2xl font-bold text-slate-950">{imageCount}</div>
              </div>
              <div className="rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">{t('Folders')}</span>
                  <Folder className="h-4 w-4 text-amber-700" />
                </div>
                <div className="mt-3 text-2xl font-bold text-slate-950">{folderCount}</div>
              </div>
              <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{t('Storage')}</span>
                  <HardDrive className="h-4 w-4 text-slate-500" />
                </div>
                <div className="mt-3 text-xl font-bold text-slate-950">{formatFileSize(totalStorageUsed)}</div>
              </div>
            </div>
          </div>

          <div className="border-t border-slate-100 bg-slate-50/70 p-5">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
              <div className="relative w-full lg:max-w-xl">
                <Search className="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                <Input
                  placeholder={t('Search files, folders, or extensions...')}
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="h-12 rounded-2xl border-slate-200 bg-white pl-12 pr-12 text-[15px] shadow-sm focus-visible:border-emerald-300 focus-visible:ring-emerald-100"
                />
                {searchTerm && (
                  <Button
                    variant="ghost"
                    size="sm"
                    className="absolute right-2 top-1/2 h-8 w-8 -translate-y-1/2 rounded-full p-0 text-slate-500 hover:bg-slate-100"
                    onClick={() => setSearchTerm('')}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                )}
              </div>
              <div className="text-sm text-slate-500">
                {searchTerm ? (
                  <span>{t('Found')} <strong className="text-slate-900">{isRootLibrary ? filteredDirectories.length + filteredMedia.length : filteredMedia.length}</strong> {t('results')}</span>
                ) : (
                  <span>{t('Showing')} <strong className="text-slate-900">{isRootLibrary ? filteredDirectories.length + 1 : filteredMedia.length}</strong> {isRootLibrary ? t('folders') : t('files')}</span>
                )}
              </div>
            </div>

            {showCreateDirectory && (
              <div className="mt-4 rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/60 p-4">
                <div className="flex flex-col gap-3 sm:flex-row">
                  <Input
                    placeholder={t('Directory name...')}
                    value={newDirectoryName}
                    onChange={(e) => setNewDirectoryName(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && createDirectory()}
                    className="h-11 rounded-xl border-emerald-100 bg-white focus-visible:border-emerald-300 focus-visible:ring-emerald-100"
                  />
                  <Button onClick={createDirectory} size="sm" className="h-11 rounded-xl bg-emerald-600 px-6 font-semibold hover:bg-emerald-700">
                    {t('Create')}
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      setShowCreateDirectory(false);
                      setNewDirectoryName('');
                    }}
                    className="h-11 rounded-xl bg-white px-6 font-semibold"
                  >
                    {t('Cancel')}
                  </Button>
                </div>
              </div>
            )}

            {editingDirectory && (
              <div className="mt-4 rounded-2xl border border-dashed border-sky-200 bg-sky-50/60 p-4">
                <div className="flex flex-col gap-3 sm:flex-row">
                  <Input
                    placeholder={t('Directory name...')}
                    value={editDirectoryName}
                    onChange={(e) => setEditDirectoryName(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && updateDirectory()}
                    className="h-11 rounded-xl border-sky-100 bg-white focus-visible:border-sky-300 focus-visible:ring-sky-100"
                  />
                  <Button onClick={updateDirectory} size="sm" className="h-11 rounded-xl px-6 font-semibold">
                    {t('Update')}
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      setEditingDirectory(null);
                      setEditDirectoryName('');
                    }}
                    className="h-11 rounded-xl bg-white px-6 font-semibold"
                  >
                    {t('Cancel')}
                  </Button>
                </div>
              </div>
            )}
          </div>
        </section>

        <Card className="overflow-hidden rounded-[2rem] border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.06)]">
          <CardContent className="p-0">
            <div className="flex flex-col gap-2 border-b border-slate-100 bg-white px-6 py-5 md:flex-row md:items-center md:justify-between">
              <div>
                <h3 className="text-lg font-bold text-slate-950">{isRootLibrary ? t('Folder workspace') : t('File workspace')}</h3>
                <p className="text-sm text-slate-500">{isRootLibrary ? t('Choose a folder or open all files.') : t('Preview, copy, download, or remove uploaded assets.')}</p>
              </div>
              <Badge variant="outline" className="w-fit rounded-full border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                {isRootLibrary ? t('Library root') : activeDirectoryName || t('All Files')}
              </Badge>
            </div>

            <div className="p-6">
              {loading ? (
                <div className="flex flex-col items-center justify-center py-20 text-center">
                  <div className="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                    <ImageIcon className="h-8 w-8 animate-pulse" />
                  </div>
                  <p className="text-base font-semibold text-slate-500">{t('Loading media...')}</p>
                </div>
              ) : (currentMedia.length === 0 && filteredDirectories.length === 0 && isRootLibrary) ? (
                <div className="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-200 bg-slate-50/70 px-6 py-20 text-center">
                  <div className="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-sm ring-1 ring-slate-200">
                    <ImageIcon className="h-8 w-8" />
                  </div>
                  <h3 className="text-xl font-bold text-slate-950">{searchTerm ? t('No results found') : t('No media files found')}</h3>
                  <p className="mt-2 max-w-md text-sm leading-6 text-slate-500">
                    {searchTerm ? t('No folders or files match "{{term}}"', { term: searchTerm }) : t('Get started by uploading your first file')}
                  </p>
                  {!searchTerm && (
                    <Button
                      onClick={() => setIsUploadModalOpen(true)}
                      className="mt-6 rounded-xl bg-emerald-600 px-6 font-semibold text-white hover:bg-emerald-700"
                    >
                      <Upload className="mr-2 h-4 w-4" />
                      {t('Upload Files')}
                    </Button>
                  )}
                </div>
              ) : currentMedia.length === 0 && (currentDirectory !== null || showAllFiles) ? (
                <div className="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-200 bg-slate-50/70 px-6 py-20 text-center">
                  <div className="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200">
                    <Folder className="h-8 w-8" />
                  </div>
                  <h3 className="text-xl font-bold text-slate-950">{t('This folder is empty')}</h3>
                  <p className="mt-2 max-w-md text-sm leading-6 text-slate-500">
                    {searchTerm ? t('No files match your search') : t('Upload files to this folder to get started')}
                  </p>
                  <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <Button
                      onClick={() => setIsUploadModalOpen(true)}
                      className="rounded-xl bg-emerald-600 px-6 font-semibold text-white hover:bg-emerald-700"
                    >
                      <Upload className="mr-2 h-4 w-4" />
                      {t('Upload Files')}
                    </Button>
                    <Button
                      variant="outline"
                      onClick={() => {
                        setCurrentDirectory(null);
                        setShowAllFiles(false);
                        setSearchTerm('');
                      }}
                      className="rounded-xl bg-white px-6 font-semibold"
                    >
                      <ArrowLeft className="mr-2 h-4 w-4" />
                      {t('Back to Library')}
                    </Button>
                  </div>
                </div>
              ) : (
                <>
                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {isRootLibrary && (!searchTerm || 'all files'.includes(searchTerm.toLowerCase())) && allFilesFolder}

                    {isRootLibrary && filteredDirectories.map((directory: any) => (
                      <div
                        key={`dir-${directory.id}`}
                        className="group flex min-h-[176px] flex-col justify-between rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)] transition-all duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_24px_60px_rgba(15,23,42,0.10)]"
                        onClick={() => {
                          setMedia([]);
                          setFilteredMedia([]);
                          setCurrentDirectory(directory.id);
                          setShowAllFiles(false);
                          setSearchTerm('');
                        }}
                      >
                        <div className="flex items-start justify-between gap-3">
                          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-100 transition-transform group-hover:scale-105">
                            <Folder className="h-6 w-6" />
                          </div>
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button
                                size="sm"
                                variant="ghost"
                                className="h-9 w-9 rounded-full p-0 text-slate-500 hover:bg-slate-100"
                                onClick={(e) => e.stopPropagation()}
                              >
                                <MoreHorizontal className="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <DropdownMenuItem onClick={(e) => {
                                e.stopPropagation();
                                setEditingDirectory(directory.id);
                                setEditDirectoryName(directory.name);
                              }}>
                                <Edit className="h-4 w-4 mr-2" />
                                {t('Edit')}
                              </DropdownMenuItem>
                              <DropdownMenuItem
                                onClick={(e) => {
                                  e.stopPropagation();
                                  setDeleteDirectoryState({isOpen: true, id: directory.id});
                                }}
                                className="text-destructive focus:text-destructive"
                              >
                                <Trash2 className="h-4 w-4 mr-2" />
                                {t('Delete')}
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </div>
                        <div className="space-y-2" onClick={() => {
                          setMedia([]);
                          setFilteredMedia([]);
                          setCurrentDirectory(directory.id);
                          setShowAllFiles(false);
                          setSearchTerm('');
                        }}>
                          <h3 className="truncate text-base font-bold text-slate-950" title={directory.name}>{directory.name}</h3>
                          <p className="flex items-center gap-2 text-sm text-slate-500">
                            <span className="h-2 w-2 rounded-full bg-amber-400" />
                            {t('Directory')}
                          </p>
                        </div>
                      </div>
                    ))}

                    {(currentDirectory !== null || showAllFiles) && currentMedia.map((item) => (
                      <div
                        key={item.id}
                        className="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.06)] transition-all duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_24px_60px_rgba(15,23,42,0.10)]"
                      >
                        <div className="relative aspect-[4/3] bg-slate-50">
                          {item.mime_type.startsWith('image/') ? (
                            <img
                              src={item.thumb_url}
                              alt={item.name}
                              className="h-full w-full object-cover"
                              onError={(e) => {
                                e.currentTarget.src = item.url;
                              }}
                            />
                          ) : (
                            <div className="flex h-full flex-col items-center justify-center p-5 text-center">
                              <div className="mb-3 transition-transform duration-300 group-hover:scale-105">
                                {getFileIcon(item.mime_type)}
                              </div>
                              <div className="max-w-full truncate rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm ring-1 ring-slate-200">
                                {item.mime_type.split('/')[1]?.toUpperCase() || 'FILE'}
                              </div>
                            </div>
                          )}
                          <div className="absolute inset-x-0 bottom-0 flex translate-y-4 items-center justify-center gap-2 bg-gradient-to-t from-slate-950/70 to-transparent p-4 opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                            <Button size="sm" variant="secondary" className="h-9 w-9 rounded-full p-0 bg-white/95" onClick={(e) => { e.stopPropagation(); handleShowInfo(item); }}>
                              <Info className="h-4 w-4" />
                            </Button>
                            <Button size="sm" variant="secondary" className="h-9 w-9 rounded-full p-0 bg-white/95" onClick={(e) => { e.stopPropagation(); handleCopyLink(item.url); }}>
                              <Copy className="h-4 w-4" />
                            </Button>
                            <Button size="sm" variant="secondary" className="h-9 w-9 rounded-full p-0 bg-white/95" onClick={(e) => { e.stopPropagation(); handleDownload(item.url); }}>
                              <Download className="h-4 w-4" />
                            </Button>
                          </div>
                          <div className="absolute left-3 top-3">
                            <Badge variant="secondary" className="rounded-full bg-white/95 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-600 shadow-sm backdrop-blur">
                              {item.mime_type.split('/')[1]?.toUpperCase() || 'FILE'}
                            </Badge>
                          </div>
                          {!infoModalOpen && !isUploadModalOpen && (
                            <div className="absolute right-3 top-3">
                              <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                  <Button size="sm" variant="secondary" className="h-9 w-9 rounded-full bg-white/95 p-0 shadow-sm backdrop-blur hover:bg-white">
                                    <MoreHorizontal className="h-4 w-4" />
                                  </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-40">
                                  <DropdownMenuItem onClick={() => handleShowInfo(item)}>
                                    <Info className="h-4 w-4 mr-2" />
                                    {t('View Info')}
                                  </DropdownMenuItem>
                                  <DropdownMenuItem onClick={() => handleCopyLink(item.url)}>
                                    <Copy className="h-4 w-4 mr-2" />
                                    {t('Copy Link')}
                                  </DropdownMenuItem>
                                  <DropdownMenuItem onClick={() => handleDownload(item.url)}>
                                    <Download className="h-4 w-4 mr-2" />
                                    {t('Download')}
                                  </DropdownMenuItem>
                                  <DropdownMenuSeparator />
                                  <DropdownMenuItem onClick={() => deleteMedia(item.id)} className="text-destructive focus:text-destructive">
                                    <Trash2 className="h-4 w-4 mr-2" />
                                    {t('Delete')}
                                  </DropdownMenuItem>
                                </DropdownMenuContent>
                              </DropdownMenu>
                            </div>
                          )}
                        </div>

                        <div className="space-y-3 p-4">
                          <h3 className="truncate text-sm font-bold text-slate-950" title={item.name}>{item.name}</h3>
                          <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span className="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 ring-1 ring-slate-200">
                              <HardDrive className="h-3 w-3" />
                              {formatFileSize(item.size)}
                            </span>
                            <span className="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 ring-1 ring-slate-200">
                              <Calendar className="h-3 w-3" />
                              {formatDate(item.created_at)}
                            </span>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>

                  {totalPages > 1 && (currentDirectory !== null || showAllFiles) && (
                    <div className="mt-6 flex flex-col items-center justify-between gap-4 border-t border-slate-100 pt-5 sm:flex-row">
                      <div className="text-sm text-slate-500">
                        {t('Showing')} <span className="font-bold text-slate-950">{startIndex + 1}</span> {t('to')} <span className="font-bold text-slate-950">{Math.min(startIndex + itemsPerPage, filteredMedia.length)}</span> {t('of')} <span className="font-bold text-slate-950">{filteredMedia.length}</span> {t('files')}
                      </div>

                      <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" disabled={currentPage === 1} onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))} className="h-10 rounded-xl bg-white px-4 font-semibold">
                          {t('Previous')}
                        </Button>
                        <div className="flex gap-1">
                          {Array.from({ length: Math.min(totalPages, 5) }, (_, i) => {
                            let page;
                            if (totalPages <= 5) {
                              page = i + 1;
                            } else if (currentPage <= 3) {
                              page = i + 1;
                            } else if (currentPage >= totalPages - 2) {
                              page = totalPages - 4 + i;
                            } else {
                              page = currentPage - 2 + i;
                            }

                            return (
                              <Button
                                key={page}
                                variant={currentPage === page ? 'default' : 'outline'}
                                size="sm"
                                className={`h-10 w-10 rounded-xl font-semibold ${currentPage === page ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-white'}`}
                                onClick={() => setCurrentPage(page)}
                              >
                                {page}
                              </Button>
                            );
                          })}
                        </div>
                        <Button variant="outline" size="sm" disabled={currentPage === totalPages} onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))} className="h-10 rounded-xl bg-white px-4 font-semibold">
                          {t('Next')}
                        </Button>
                      </div>
                    </div>
                  )}
                </>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Upload Modal */}
        <Dialog open={isUploadModalOpen} onOpenChange={setIsUploadModalOpen}>
          <DialogContent className="max-w-2xl" onInteractOutside={(e) => e.preventDefault()}>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-3 text-xl">
                <div className="p-2 bg-primary/10 rounded-lg">
                  <Upload className="h-6 w-6 text-primary" />
                </div>
                {t('Upload Files')}
              </DialogTitle>
              <DialogDescription className="text-base">
                {t('Upload new files to your media library')}
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-6">
              <div
                className={`relative border-3 border-dashed rounded-2xl p-16 text-center transition-all duration-300 ${
                  dragActive
                    ? 'border-primary bg-primary/10 scale-[1.02] shadow-lg'
                    : 'border-muted-foreground/30 hover:border-primary/50 hover:bg-muted/50'
                }`}
                onDragEnter={handleDrag}
                onDragLeave={handleDrag}
                onDragOver={handleDrag}
                onDrop={handleDrop}
              >
                <div className={`transition-all duration-300 ${
                  dragActive ? 'scale-110' : ''
                }`}>
                  <div className={`mx-auto w-20 h-20 rounded-2xl flex items-center justify-center mb-6 transition-all duration-300 ${
                    dragActive ? 'bg-primary shadow-lg shadow-primary/50' : 'bg-gradient-to-br from-primary/20 to-primary/10'
                  }`}>
                    <Upload className={`h-10 w-10 transition-all duration-300 ${
                      dragActive ? 'text-white animate-bounce' : 'text-primary'
                    }`} />
                  </div>
                  <h3 className="text-xl font-semibold mb-3">
                    {dragActive ? '✨ ' + t('Drop files here') + ' ✨' : '📁 ' + t('Upload your files')}
                  </h3>
                  <p className="text-base text-muted-foreground mb-8 max-w-md mx-auto">
                    {dragActive 
                      ? t('Release to upload your files')
                      : t('Drag and drop your files here, or click to browse')
                    }
                  </p>

                  <Input
                    type="file"
                    multiple
                    onChange={(e) => e.target.files && handleFileUpload(e.target.files)}
                    className="hidden"
                    id="file-upload-modal"
                  />

                  {!uploading && (
                    <Button
                      type="button"
                      onClick={() => document.getElementById('file-upload-modal')?.click()}
                      size="lg"
                      className="h-12 px-8 text-base font-semibold shadow-lg"
                    >
                      <Plus className="h-5 w-5 mr-2" />
                      {t('Choose Files')}
                    </Button>
                  )}

                  {uploading && (
                    <div className="space-y-4">
                      <div className="flex items-center justify-center gap-3">
                        <div className="animate-spin rounded-full h-6 w-6 border-3 border-primary border-t-transparent"></div>
                        <span className="text-lg font-semibold text-primary">{t('Uploading...')}</span>
                      </div>
                      <div className="w-full max-w-xs mx-auto h-2 bg-muted rounded-full overflow-hidden">
                        <div className="h-full bg-gradient-to-r from-primary to-primary/60 animate-pulse rounded-full" style={{width: '100%'}}></div>
                      </div>
                    </div>
                  )}
                </div>

                {dragActive && (
                  <div className="absolute inset-0 bg-primary/5 rounded-2xl pointer-events-none" />
                )}
              </div>

              {/* File Type Info */}
              <div className="bg-muted/50 rounded-xl p-4 border">
                <div className="flex items-start gap-3">
                  <div className="p-2 bg-primary/10 rounded-lg mt-0.5">
                    <Info className="h-4 w-4 text-primary" />
                  </div>
                  <div className="flex-1 text-sm">
                    <p className="font-medium mb-1">{t('Supported file types')}</p>
                    <p className="text-muted-foreground leading-relaxed">
                      {t('Images, Documents, Videos, and more. Check storage settings for specific formats.')}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </DialogContent>
        </Dialog>

        {/* Info Modal */}
        <Dialog open={infoModalOpen} onOpenChange={setInfoModalOpen}>
          <DialogContent className="max-w-2xl" onInteractOutside={(e) => e.preventDefault()}>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-3 text-xl">
                <div className="p-2 bg-primary/10 rounded-lg">
                  <Info className="h-6 w-6 text-primary" />
                </div>
                {t('File Information')}
              </DialogTitle>
              <DialogDescription className="text-base">
                {t('View detailed information about this file')}
              </DialogDescription>
            </DialogHeader>

            {selectedMediaInfo && (
              <div className="space-y-6">
                {/* File Preview */}
                <div className="flex justify-center bg-gradient-to-br from-muted/50 to-muted rounded-2xl p-6 border-2">
                  {selectedMediaInfo.mime_type.startsWith('image/') ? (
                    <img
                      src={selectedMediaInfo.thumb_url}
                      alt={selectedMediaInfo.name}
                      className="max-w-full h-64 object-contain rounded-xl shadow-2xl"
                      onError={(e) => {
                        e.currentTarget.src = selectedMediaInfo.url;
                      }}
                    />
                  ) : (
                    <div className="flex flex-col items-center justify-center h-64 w-full">
                      <div className="mb-6 p-6 bg-background rounded-2xl shadow-lg">
                        <div className="text-7xl">
                          {getFileIcon(selectedMediaInfo.mime_type)}
                        </div>
                      </div>
                      <div className="text-base font-semibold text-muted-foreground">
                        {selectedMediaInfo.mime_type.split('/')[1]?.toUpperCase() || 'FILE'}
                      </div>
                    </div>
                  )}
                </div>

                {/* File Details */}
                <div className="grid grid-cols-1 gap-4">
                  <div className="space-y-4 bg-muted/30 rounded-xl p-5 border">
                    <div className="flex justify-between items-start py-2 border-b">
                      <span className="text-sm font-semibold text-muted-foreground flex items-center gap-2">
                        <span className="w-2 h-2 rounded-full bg-primary"></span>
                        {t('File Name')}
                      </span>
                      <span className="text-sm font-medium text-right max-w-xs truncate" title={selectedMediaInfo.file_name}>
                        {selectedMediaInfo.file_name}
                      </span>
                    </div>

                    <div className="flex justify-between items-center py-2 border-b">
                      <span className="text-sm font-semibold text-muted-foreground flex items-center gap-2">
                        <span className="w-2 h-2 rounded-full bg-blue-500"></span>
                        {t('File Type')}
                      </span>
                      <Badge variant="secondary" className="font-semibold">{selectedMediaInfo.mime_type}</Badge>
                    </div>

                    <div className="flex justify-between items-center py-2 border-b">
                      <span className="text-sm font-semibold text-muted-foreground flex items-center gap-2">
                        <span className="w-2 h-2 rounded-full bg-green-500"></span>
                        {t('File Size')}
                      </span>
                      <span className="text-sm font-medium">{formatFileSize(selectedMediaInfo.size)}</span>
                    </div>

                    <div className="flex justify-between items-center py-2">
                      <span className="text-sm font-semibold text-muted-foreground flex items-center gap-2">
                        <span className="w-2 h-2 rounded-full bg-purple-500"></span>
                        {t('Uploaded')}
                      </span>
                      <span className="text-sm font-medium">{formatDate(selectedMediaInfo.created_at)}</span>
                    </div>
                  </div>

                  <div className="pt-2 bg-muted/30 rounded-xl p-5 border">
                    <span className="text-sm font-semibold text-muted-foreground flex items-center gap-2 mb-3">
                      <span className="w-2 h-2 rounded-full bg-orange-500"></span>
                      {t('URL')}
                    </span>
                    <div className="flex items-center gap-2 p-3 bg-background rounded-lg border">
                      <code className="text-xs text-muted-foreground flex-1 truncate font-mono">
                        {selectedMediaInfo.url}
                      </code>
                      <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => handleCopyLink(selectedMediaInfo.url)}
                        className="h-8 w-8 p-0 hover:bg-primary/10"
                      >
                        <Copy className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </div>

                {/* Actions */}
                <div className="flex gap-3 pt-2">
                  <Button
                    variant="outline"
                    onClick={() => handleCopyLink(selectedMediaInfo.url)}
                    className="flex-1 h-11"
                  >
                    <Copy className="h-4 w-4 mr-2" />
                    {t('Copy Link')}
                  </Button>
                  <Button
                    variant="outline"
                    onClick={() => handleDownload(selectedMediaInfo.url)}
                    className="flex-1 h-11"
                  >
                    <Download className="h-4 w-4 mr-2" />
                    {t('Download')}
                  </Button>
                </div>
              </div>
            )}
          </DialogContent>
        </Dialog>
      </div>

      <ConfirmationDialog
        open={deleteDirectoryState.isOpen}
        onOpenChange={(open) => setDeleteDirectoryState({isOpen: open, id: null})}
        title={t('Delete Directory')}
        message={t('Are you sure you want to delete this directory?')}
        confirmText={t('Delete')}
        onConfirm={() => deleteDirectoryState.id && deleteDirectory(deleteDirectoryState.id)}
        variant="destructive"
      />
    </AuthenticatedLayout>
  );
}
