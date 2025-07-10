import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import {
  Container,
  Grid,
  Typography,
  Box,
  Chip,
  Button,
  Card,
  CardContent,
  Divider,
  List,
  ListItem,
  ListItemText,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Alert,
  Skeleton,
  useTheme,
  useMediaQuery,
  Paper,
} from '@mui/material';
import { BookmarkAdd as BookmarkAddIcon } from '@mui/icons-material';
import axios from 'axios';
import { useAuth } from '../contexts/AuthContext';

function OeuvreDetail() {
  const { id } = useParams();
  const { isAuthenticated } = useAuth();
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));
  const isTablet = useMediaQuery(theme.breakpoints.between('sm', 'md'));
  
  const [oeuvre, setOeuvre] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [openDialog, setOpenDialog] = useState(false);
  const [selectedCollection, setSelectedCollection] = useState('');
  const [collections, setCollections] = useState([]);
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    const fetchOeuvre = async () => {
      try {
        const response = await axios.get(`/oeuvres/${id}`);
        setOeuvre(response.data);
      } catch (error) {
        setError('Erreur lors du chargement de l\'œuvre');
      } finally {
        setLoading(false);
      }
    };

    fetchOeuvre();
  }, [id]);

  const handleOpenDialog = async () => {
    if (isAuthenticated) {
      try {
        const response = await axios.get('/collections');
        setCollections(response.data['hydra:member']);
        setOpenDialog(true);
      } catch (error) {
        setError('Erreur lors du chargement des collections');
      }
    }
  };

  const handleCloseDialog = () => {
    setOpenDialog(false);
    setSelectedCollection('');
  };

  const handleAddToCollection = async () => {
    try {
      await axios.post('/collections', {
        oeuvre: `/oeuvres/${id}`,
        collection: selectedCollection,
      });
      setSuccessMessage('Œuvre ajoutée à la collection avec succès');
      handleCloseDialog();
    } catch (error) {
      setError('Erreur lors de l\'ajout à la collection');
    }
  };

  if (loading) {
    return (
      <Container maxWidth="lg" sx={{ py: { xs: 2, sm: 3, md: 4 } }}>
        <Grid container spacing={{ xs: 2, sm: 3, md: 4 }}>
          <Grid item xs={12} md={4}>
            <Skeleton variant="rectangular" height={400} />
          </Grid>
          <Grid item xs={12} md={8}>
            <Skeleton variant="text" height={60} />
            <Skeleton variant="text" height={40} width="60%" />
            <Box sx={{ my: 2 }}>
              <Skeleton variant="rectangular" height={32} width={100} />
            </Box>
            <Skeleton variant="text" height={100} />
          </Grid>
        </Grid>
      </Container>
    );
  }

  if (error) {
    return (
      <Container maxWidth="lg" sx={{ py: { xs: 2, sm: 3, md: 4 } }}>
        <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>
      </Container>
    );
  }

  if (!oeuvre) {
    return (
      <Container maxWidth="lg" sx={{ py: { xs: 2, sm: 3, md: 4 } }}>
        <Alert severity="info">Œuvre non trouvée</Alert>
      </Container>
    );
  }

  return (
    <Container maxWidth="lg" sx={{ py: { xs: 2, sm: 3, md: 4 } }}>
      {successMessage && (
        <Alert severity="success" sx={{ mb: 2 }} onClose={() => setSuccessMessage('')}>
          {successMessage}
        </Alert>
      )}
      
      <Paper elevation={2} sx={{ p: { xs: 2, sm: 3 }, mb: { xs: 3, sm: 4 } }}>
        <Grid container spacing={{ xs: 2, sm: 3, md: 4 }}>
          <Grid item xs={12} md={4}>
            <Box
              component="img"
              src={oeuvre.image || '/placeholder.jpg'}
              alt={oeuvre.titre}
              sx={{
                width: '100%',
                height: 'auto',
                maxHeight: { xs: 400, md: 600 },
                objectFit: 'cover',
                borderRadius: 1,
                boxShadow: 3,
              }}
            />
          </Grid>
          <Grid item xs={12} md={8}>
            <Typography 
              variant="h4" 
              component="h1" 
              gutterBottom
              sx={{ 
                fontSize: { xs: '1.75rem', sm: '2.25rem', md: '2.5rem' },
                lineHeight: 1.2
              }}
            >
              {oeuvre.titre}
            </Typography>
            <Typography 
              variant="h6" 
              color="text.secondary" 
              gutterBottom
              sx={{ 
                fontSize: { xs: '1.1rem', sm: '1.25rem' },
                mb: 2
              }}
            >
              {oeuvre.auteur.nom} {oeuvre.auteur.prenom}
            </Typography>
            <Box sx={{ 
              my: { xs: 2, sm: 3 },
              display: 'flex',
              flexWrap: 'wrap',
              gap: 1
            }}>
              {oeuvre.tags.map((tag) => (
                <Chip
                  key={tag.id}
                  label={tag.nom}
                  size={isMobile ? "medium" : "small"}
                  sx={{ 
                    borderRadius: '16px',
                    '&:hover': {
                      backgroundColor: 'primary.main',
                      color: 'white',
                    }
                  }}
                />
              ))}
            </Box>
            <Typography 
              variant="body1" 
              paragraph
              sx={{ 
                fontSize: { xs: '0.9rem', sm: '1rem' },
                lineHeight: 1.6,
                mb: { xs: 2, sm: 3 }
              }}
            >
              {oeuvre.description}
            </Typography>
            {isAuthenticated && (
              <Button
                variant="contained"
                color="primary"
                onClick={handleOpenDialog}
                startIcon={<BookmarkAddIcon />}
                sx={{ 
                  mt: { xs: 1, sm: 2 },
                  width: { xs: '100%', sm: 'auto' }
                }}
              >
                Ajouter à une collection
              </Button>
            )}
          </Grid>
        </Grid>
      </Paper>

      <Divider sx={{ my: { xs: 3, sm: 4 } }} />

      <Typography 
        variant="h5" 
        gutterBottom
        sx={{ 
          fontSize: { xs: '1.5rem', sm: '1.75rem' },
          mb: { xs: 2, sm: 3 }
        }}
      >
        Chapitres
      </Typography>
      
      <Box sx={{ mb: { xs: 2, sm: 3 } }}>
        {oeuvre.chapitres.map((chapitre) => (
          <Card 
            key={chapitre.id} 
            sx={{ 
              mb: 2,
              transition: 'transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out',
              '&:hover': {
                transform: 'translateY(-2px)',
                boxShadow: 4,
              }
            }}
          >
            <CardContent sx={{ p: { xs: 2, sm: 3 } }}>
              <Typography 
                variant="h6"
                sx={{ 
                  fontSize: { xs: '1.1rem', sm: '1.25rem' },
                  mb: 1
                }}
              >
                {chapitre.titre}
              </Typography>
              <Typography 
                variant="body2" 
                color="text.secondary"
                sx={{ 
                  fontSize: { xs: '0.875rem', sm: '1rem' }
                }}
              >
                {chapitre.contenu}
              </Typography>
            </CardContent>
          </Card>
        ))}
      </Box>

      <Dialog 
        open={openDialog} 
        onClose={handleCloseDialog}
        fullWidth
        maxWidth="sm"
        PaperProps={{
          sx: {
            borderRadius: 2,
            p: { xs: 1, sm: 2 }
          }
        }}
      >
        <DialogTitle sx={{ 
          fontSize: { xs: '1.25rem', sm: '1.5rem' },
          pb: 1
        }}>
          Ajouter à une collection
        </DialogTitle>
        <DialogContent>
          <FormControl fullWidth sx={{ mt: 2 }}>
            <InputLabel>Collection</InputLabel>
            <Select
              value={selectedCollection}
              onChange={(e) => setSelectedCollection(e.target.value)}
              label="Collection"
            >
              {collections.map((collection) => (
                <MenuItem key={collection.id} value={collection.id}>
                  {collection.nom}
                </MenuItem>
              ))}
            </Select>
          </FormControl>
        </DialogContent>
        <DialogActions sx={{ p: 2 }}>
          <Button 
            onClick={handleCloseDialog}
            sx={{ 
              fontSize: { xs: '0.875rem', sm: '1rem' }
            }}
          >
            Annuler
          </Button>
          <Button
            onClick={handleAddToCollection}
            variant="contained"
            disabled={!selectedCollection}
            sx={{ 
              fontSize: { xs: '0.875rem', sm: '1rem' }
            }}
          >
            Ajouter
          </Button>
        </DialogActions>
      </Dialog>
    </Container>
  );
}

export default OeuvreDetail; 